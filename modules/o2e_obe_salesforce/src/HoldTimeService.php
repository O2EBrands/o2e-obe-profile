<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Hold Time Servicee class is hold the serviceid time .
 */
class HoldTimeService {


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
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
  }

  /**
   * Holdtime method is hold the service id time.
   */
  public function holdtime(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_hold_time.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');

    $headers = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options += [
      'service_id' => $tempstore['service_id'],
    ];
    // Request object for Email entry.
    $request = UrlHelper::buildQuery($options);
    try {
      $startHoldTimeTimer = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $endHoldTimeTimer = $this->timeService->getCurrentMicroTime();
      // Logs the Timer HoldTime.
      $holdTimeTimerDuration = round($endHoldTimeTimer - $startHoldTimeTimer, 2);
      $availabilityTimerDuration = "API response time: " . $holdTimeTimerDuration;
      $zipCode = "Zip code: " . $tempstore['from_postal_code'];
      $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
      $this->obeSfLogger->log('Timer HoldTime', 'notice', $zipCode . " // " .
      $availabilityTimerDuration . " // " .
      $userAgent);

      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . '  -----  ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - Hold Time', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options,
        'response' => $result,
      ]);
      // Tempstore to store holdtime request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('holdtime', [
        'name' => 'Hold Time',
        'url' => $api_url,
        'request' => $request,
        'response' => $result,
      ]);
      return $response->getStatusCode();
    }
    catch (RequestException $e) {
      // Tempstore to store holdtime request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('holdtime', [
        'name' => 'Hold Time',
        'url' => $api_url,
        'request' => $request,
        'response' => $e->getMessage(),
      ]);
      $this->obeSfLogger->log('Salesforce - Hold Time Fail', 'error', $e->getMessage());
    }
  }

}
