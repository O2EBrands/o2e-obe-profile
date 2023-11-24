<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Area Verification Service class is return the area details.
 */
class AreaVerificationService {


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
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

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
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, TimeInterface $time_service, 
    State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, 
    RequestStack $request_stack, ConfigFactoryInterface $config, DataDogService $data_dog_manager) {

    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->timeService = $time_service;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->request = $request_stack;
    $this->dataDogService = $data_dog_manager;   
  }

  /**
   * Verify the area on the basis of zip code.
   */
  public function verifyAreaCode(string $zipcode) {
    $options = [];
    $currentTimeStamp = $this->timeService->getRequestTime();
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_verify_area.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');
    $check_expiry = $this->checkExpiry($currentTimeStamp);
    if ($check_expiry && $sf_response['from_postal_code'] == $zipcode) {
      return $check_expiry;
    }

    $options['headers'] = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options['query'] = [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'from_postal_code' => $zipcode,
    ];
    // Request object for Email entry.
    $request = $api_url . '?' . UrlHelper::buildQuery($options['query']);
    try {
      $startZipTimer = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('GET', $api_url, $options);
      $endZipTimer = $this->timeService->getCurrentMicroTime();
      // Logs the Timer VerifyAreaServiced.
      $duration = round($endZipTimer - $startZipTimer, 2);
      $availabilityTimerDuration = "API response time: " . $duration;
      $zipCode = "Zip code: " . $zipcode;
      $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
      $this->obeSfLogger->log('Timer VerifyAreaServiced', 'notice', $zipCode . " // " .
      $availabilityTimerDuration . " // " .
      $userAgent);
      $result = Json::decode($response->getBody(), TRUE);
      $tempstore->set('response', [
        'service_id' => $result['service_id'],
        'from_postal_code' => $result['from_postal_code'],
        'franchise_id' => $result['franchise_id'],
        'franchise_name' => $result['franchise_name'],
        'job_duration' => $result['job_duration'] ?? '',
        'lastServiceTime' => $currentTimeStamp,
        'state' => $result['state'] ?? '',
      ]);
      $data = UrlHelper::buildQuery($options['query']) . '  -----  ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - VerifyAreaServiced', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'GET',
        'payload' => $options['query'],
        'response' => $result,
      ]);
      // Datadog
      $this->dataDogService->createSuccessDatadog('Salesforce - Verify Area Serviced', 'GET', $api_url, $response, $duration); 
      // Tempstore to store areaverification request log.
      $tempstore->set('areaverification', [
        'name' => 'Verify Area Serviced',
        'request' => $request,
        'response' => $result,
      ]);
      return $result;
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - VerifyAreaServiced Fail', 'error', $e->getMessage());
      // Datadog
      $this->dataDogService->createFailDatadog('Salesforce - VerifyAreaServiced Fail', 'GET', $api_url, $e ); 
      if (!empty($e->getResponse())) {
        // Tempstore to store areaverification request log.
        $tempstore->set('areaverification', [
          'name' => 'Verify Area Serviced',
          'request' => $request,
          'response' => $e->getResponseBodySummary($e->getResponse()),
        ]);
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse()),
        ];
      }
    }
  }

  /**
   * Check service id expiry.
   */
  public function checkExpiry($currentTimeStamp) {
    /* If last authentication was in last 15 min (900 seconds),
     * return area response, else call again.
     */
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
    if (!empty($tempstore) && array_key_exists('lastServiceTime', $tempstore)) {
      $timeDifference = $currentTimeStamp - $tempstore['lastServiceTime'];
      if ($timeDifference < $this->authTokenManager->getSfConfig('sf_verify_area.service_expiry')) {
        return $tempstore;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

}
