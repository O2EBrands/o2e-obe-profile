<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Http\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Available Times Service class is return the time slots details.
 */
class AvailableTimesService {


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
   * The Area Verification Service.
   *
   * @var \Drupal\o2e_obe_salesforce\AreaVerificationService
   */
  protected $areaVerification;

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
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;
  /**
   * Get the sfConfig values.
   *
   * @var \object|null
   */
  protected $ddConfig;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service, AreaVerificationService $area_verification, RequestStack $request_stack, AccountInterface $account, ConfigFactoryInterface $config) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
    $this->areaVerification = $area_verification;
    $this->request = $request_stack;
    $this->account = $account;
    $this->ddConfig = $config->get('o2e_obe_salesforce.datadog_settings');
  }

  /**
   * Get the time slots on the basis of start and end date.
   */
  public function getAvailableTimes(array $options = []) {
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

    $currentTimeStamp = $this->timeService->getRequestTime();
    $auth_token = $this->state->get('authtoken');
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');
    if ($sf_response['lastServiceTime']) {
      $timeDifference = $currentTimeStamp - $sf_response['lastServiceTime'];
      if ($timeDifference > $this->authTokenManager->getSfConfig('sf_verify_area.service_expiry')) {
        $this->areaVerification->verifyAreaCode($sf_response['from_postal_code']);
        $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
        $sf_response = $tempstore->get('response');
      }
    }
    $api_url = $this->authTokenManager->getSfConfig('sf_available_time.api_url_segment');
    if (!empty($auth_token)) {
      if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
        if (substr($api_url, 0, 1) !== '/') {
          $api_url = $this->state->get('sfUrl') . '/' . $api_url;
        }
        else {
          $api_url = $this->state->get('sfUrl') . $api_url;
        }
      }
      $job_duration = $sf_response['job_duration'];
      $job_duration = str_replace([' hours', ' hour'], '', $job_duration);
      if (strpos($job_duration, "min") > 0 || strpos($job_duration, "Minutes") > 0 || strpos($job_duration, "minutes")) {
        $job_duration = 0.5;
      }
      $headers = [
        'Authorization' => $auth_token,
        'content-type' => 'application/json',
      ];
      $brand = $this->authTokenManager->getSfConfig('sf_brand.brand');
      if (!empty($brand)) {
        if ($brand === '1-800-GOT-JUNK?') {
          $options += [
            "franchise_id" => $sf_response['franchise_id'],
            "brand" => $this->authTokenManager->getSfConfig('sf_brand.brand'),
            "service_type" => $this->authTokenManager->getSfConfig('sf_available_time.services_type'),
            "postal_code" => $sf_response['from_postal_code'],
            "service_id" => $sf_response['service_id'],
            "job_duration" => $job_duration,
          ];
        }
        if ($brand === 'Shack Shine') {
          $options += [
            'opportunity_id' => $this->tempStoreFactory->get('o2e_obe_salesforce')->get('getPrice')['opportunity_id'] ?? '',
            'postal_code' => $sf_response['from_postal_code'],
            'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
            'service_type' => $this->tempStoreFactory->get('o2e_obe_salesforce')->get('service_type') ?? $this->authTokenManager->getSfConfig('sf_available_time.services_type'),
            'service_id' => $sf_response['service_id'],
          ];
        }
        if ($brand === 'WOW 1 DAY PAINTING') {
          $interior_or_exterior = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('interior_or_exterior');
          $customer_type = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('property_type');
          $exterior = $interior = FALSE;
          switch ($interior_or_exterior) {
            case 'Exterior':
              $exterior = TRUE;
              break;

            case 'Interior':
              $interior = TRUE;
              break;

            case 'Both':
              $exterior = TRUE;
              $interior = TRUE;
              break;
          }
          if ($customer_type == 'Residential') {
            $service_type = $exterior ? 'W1D - Residential Exterior Estimate' : 'W1D - Residential Interior Estimate';
          }
          else {
            $service_type = 'W1D - Commercial Estimate';
          }
          $options += [
            'franchise_id' => $sf_response['franchise_id'],
            'service_id' => $sf_response['service_id'] ?? '',
            'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
            'postal_code' => $sf_response['from_postal_code'],
            'interior' => $interior,
            'exterior' => $exterior,
            'customer_type' => $customer_type,
            'service_type' => $service_type,
          ];
        }
      }
      // Request object for Email entry.
      $request = UrlHelper::buildQuery($options);
      try {
        $startAvailabilityTimer = $this->timeService->getCurrentMicroTime();
        $res = $this->httpClient->request('POST', $api_url, [
          'headers' => $headers,
          'json' => $options,
        ])->getBody();
        $endAvailabilityTimer = $this->timeService->getCurrentMicroTime();
        // Logs the Timer GetAvailableTimes.
        $availabilityTimerDuration = "API response time: " . round($endAvailabilityTimer - $startAvailabilityTimer, 2);
        $zipCode = "Zip code: " . $sf_response['from_postal_code'];
        $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
        $this->obeSfLogger->log('Timer GetAvailableTimes', 'notice', $zipCode . " // " .
          $availabilityTimerDuration . " // " .
          $userAgent);
        $result = Json::decode($res, TRUE);
        $current_time = \Drupal::service('datetime.time')->getRequestTime();
        $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('currentLocalTime');
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('currentLocalTime', [
          'currentTimeStamp' => $current_time,
        ]);
        $data = UrlHelper::buildQuery($options) . '  -----  ' . Json::encode($result);
        $this->obeSfLogger->log('Salesforce - current time set', 'notice', $current_time);
        $this->obeSfLogger->log('Salesforce - GetAvailableTimes', 'notice', $data, [
          'request_url' => $api_url,
          'type' => 'POST',
          'payload' => $options,
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
                'service' => 'Timer GetAvailableTimes',
              ],
              [
                'ddsource' => 'drupal',
                'ddtags' => $dd_env,
                'hostname' => $hostname,
                'message' => $data,
                'service' => 'Salesforce - GetAvailableTimes',
              ],
            ],
            'headers' => $dd_headers,
          ]);
        }
        catch (RequestException $e) {
        }
        // End of datadog implementation.
        // Tempstore to store availableTimes request log.
        $tempstore->set('availableTimes', [
          'name' => 'Get Available Times',
          'url' => $api_url,
          'request' => $request,
          'response' => $result,
        ]);
        return $result;
      }
      catch (RequestException $e) {
        // Tempstore to store availableTimes request log.
        $tempstore->set('availableTimes', [
          'name' => 'Get Available Times',
          'url' => $api_url,
          'request' => $request,
          'response' => $e->getMessage(),
        ]);
        $this->obeSfLogger->log('Salesforce - GetAvailableTimes Fail', 'error', $e->getMessage());
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
              'service' => 'Salesforce - GetAvailableTimes Fail',
              "status" => 'error',
            ],
            ],
            'headers' => $dd_headers,
          ]);
        }
        catch (RequestException $e) {
        }
        // End of datadog implementation.
      }
    }
  }

  /**
   * Get the time slots on the basis of start and end date form URL.
   */
  public function getTimesSlots() {
    $params = $this->request->getCurrentRequest();
    $referer_url = $params->headers->get('referer');
    // Updating slot request to 1 day for Clone V2.
    $pattern_v2 = "/onlinebooking2/i";
    if (isset($referer_url) && preg_match($pattern_v2, $referer_url)) {
      $start_date = $params->get('start_date');
      if ($start_date) {
        $response = $this->getAvailableTimes([
          'start_date' => $start_date,
          'end_date' => $start_date,
        ]);
        if (in_array("administrator", $this->account->getRoles())) {
          $this->tempStoreFactory->get('o2e_obe_salesforce')->set('getAvailableTimesSlots', $response);
        }
        return new JsonResponse($response);
      }
      else {
        return FALSE;
      }
    }
    else {
      $pattern_v1 = "/onlinebooking/i";
      $start_date = $params->get('start_date');
      $end_date = $params->get('end_date');
      if (isset($referer_url) && preg_match($pattern_v1, $referer_url)) {
        if ($start_date && $end_date) {
          $response = $this->getAvailableTimes([
            'start_date' => $start_date,
            'end_date' => $end_date,
          ]);
          if (in_array("administrator", $this->account->getRoles())) {
            $this->tempStoreFactory->get('o2e_obe_salesforce')->set('getAvailableTimesSlots', $response);
          }
          return new JsonResponse($response);
        }
        else {
          return FALSE;
        }
    }
    else {
      $response = [         
        'message' => 'Salesforce - GetAvailableTimes Fail, Invalid request.',
      ];
      return new JsonResponse($response);
      }
    }
  }

  /**
    *  Validate Request to the SalesForce OBE
  */
  public function IsSalesForceValidRequest($options) { 
    $mandatory_fields =['franchise_id','service_id','brand','start_date','end_date','postal_code'];
    $missing_fields = [];
    foreach ($mandatory_fields as $key) { 
      if (empty($options[$key])) {
        $missing_fields[] = $key;
        } 
      }
      if(!empty($missing_fields)){
          return FALSE;
      }else{
          return TRUE;
      }
  }

}
