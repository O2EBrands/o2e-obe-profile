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
   * Get the sfConfig values.
   *
   * @var \object|null
   */
  protected $ddConfig;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, TimeInterface $time_service, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, RequestStack $request_stack, ConfigFactoryInterface $config) {
    $this->ddConfig = $config->get('o2e_obe_salesforce.datadog_settings');
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->timeService = $time_service;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->request = $request_stack;
  }

  /**
   * Verify the area on the basis of zip code.
   */
  public function verifyAreaCode(string $zipcode) {
    // Variables for Datadog.
    $hostname = $this->request->getCurrentRequest()->getSchemeAndHttpHost();
    $dd_env = (!empty($_ENV["PANTHEON_ENVIRONMENT"])) ? 'env: ' . $_ENV["PANTHEON_ENVIRONMENT"] : '';
    $dd_api_key = $this->ddConfig->get('dd_config.api_key') ?? '';
    $datadog_url = $this->ddConfig->get('dd_config.api_url') ?? '';
    $dd_headers = [
      'Accept' => 'application/json',
      'Content-type' => 'application/json',
      'DD-API-KEY' => $dd_api_key,
    ];

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
      $zipTimerDuration = round($endZipTimer - $startZipTimer, 2);
      $availabilityTimerDuration = "API response time: " . $zipTimerDuration;
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

      // Datadog Implementation - Timer - Verify Area Serviced.
      try {
        $this->httpClient->request('POST', $datadog_url, [
          'verify' => TRUE,
          'json' => [
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $zipCode . " // " .
              $availabilityTimerDuration . " // " .
              $userAgent,
              'service' => 'Timer - Verify Area Serviced',
            ],
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $data,
              'service' => 'Salesforce - Verify Area Serviced',
            ],
          ],
          'headers' => $dd_headers,
        ]);
      }
      catch (RequestException $e) {
      }
      // End of datadog implementation.
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
      // Datadog Implementation - Timer - Verify Area Serviced.
      try {
        $this->httpClient->request('POST', $datadog_url, [
          'verify' => TRUE,
          'json' => [
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $e->getMessage(),
              'service' => 'Salesforce - VerifyAreaServiced Fail',
              "status" => 'error',
            ],
          ],
          'headers' => $dd_headers,
        ]);
      }
      catch (RequestException $e) {
      }
      // End of datadog implementation.
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
