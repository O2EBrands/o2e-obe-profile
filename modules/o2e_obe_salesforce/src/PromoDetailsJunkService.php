<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\ClientException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

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
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

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
   * Constructor method.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger_factory, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;

  }

  /**
   * Return the promo code data.
   */
  public function getPromocode(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_promo_details_junk.api_url_segment');
    if (substr($endpoint_segment, 0, 1) !== '/') {
      $endpoint_segment = '/' . $endpoint_segment;
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
    $api_url = $this->state->get('sfUrl') . $endpoint_segment;

    $options['headers'] = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options['query'] += [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'franchise_id' => $tempstore['franchise_id'],
    ];
    try {
      $response = $this->httpClient->request('GET', $api_url, $options);
      $result = Json::decode($response->getBody(), TRUE);
      $this->loggerFactory->get('Salesforce - Promo Details Junk')->notice(UrlHelper::buildQuery($options['query']) . ' ' . Json::encode($result));
      return $result;
    }
    catch (ClientException $e) {
      $this->loggerFactory->get('Salesforce - Promo Details Junk Fail')->error($e->getMessage());
    }
  }

}
