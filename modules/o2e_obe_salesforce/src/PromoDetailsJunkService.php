<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Promo Details Junk Service class is return the promo details.
 */
class PromoDetailsJunkService {


  /**
   * PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Obe Sf Logger.
   *
   * @var \Drupal\o2e_obe_salesforce\ObeSfLogger
   */
  protected $obeSfLogger;

  /**
   * The object State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The Auth Token Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\AuthTokenManager
   */
  protected $authTokenManager;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;
  /**
   * Request stack.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected $request;

  /**
   * Data dog service.
   *
   * @var \Drupal\o2e_obe_salesforce\DataDogService
   */
  protected $dataDogService;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, 
    PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, 
    TimeInterface $time_service, RequestStack $request_stack, ConfigFactoryInterface $config, DataDogService $data_dog_manager) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
    $this->request = $request_stack;
    $this->dataDogService = $data_dog_manager;   
  }

  /**
   * Return the promo code data.
   */
  public function getPromocode(string $promocode) {
    $options = [];
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_promo_details_junk.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');

    $options['headers'] = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options['query'] = [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'franchise_id' => $tempstore['franchise_id'],
      'promotion_code' => $promocode,
    ];
    // Request object for Email entry.
    $request = $api_url . '?' . UrlHelper::buildQuery($options['query']);
    try {
      $startPromoTimer = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('GET', $api_url, $options);
      $endPromoTimer = $this->timeService->getCurrentMicroTime();
      // Logs the Timer PromoDetailsJunk.
      $duration = round($endPromoTimer - $startPromoTimer, 2);
      $this->obeSfLogger->log('Timer PromoDetailsJunk', 'notice', $duration);
      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options['query']) . ' ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - Promo Details Junk', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'GET',
        'payload' => $options['query'],
        'response' => $result,
      ]);

      // Datadog
      $this->dataDogService->createSuccessDatadog('Salesforce - Promo Details Junk', 'GET', $api_url, $response, $duration); 

      // Tempstore to store promoDetails request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('promoDetails', [
        'name' => 'Promo Details Junk',
        'request' => $request,
        'response' => $result,
      ]);
      return $result;
    }
    catch (RequestException $e) {
      // Tempstore to store promoDetails request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('promoDetails', [
        'name' => 'Promo Details Junk',
        'request' => $request,
        'response' => $e->getMessage(),
      ]);
      $this->obeSfLogger->log('Salesforce - Promo Details Junk Fail', 'error', $e->getMessage());
      // Datadog
      $this->dataDogService->createFailDatadog('Salesforce - Promo Details Junk Fail', 'GET', $api_url, $e); 
    }
  }

}
