<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Http\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

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
   * Constructor method.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger_factory, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service, AreaVerificationService $area_verification, RequestStack $request_stack) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
    $this->areaVerification = $area_verification;
    $this->request = $request_stack;

  }

  /**
   * Get the time slots on the basis of start and end date.
   */
  public function getAvailableTimes(array $options = []) {
    $currentTimeStamp = $this->timeService->getRequestTime();
    $auth_token = $this->state->get('authtoken');
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');
    if ($sf_response['lastServiceTime']) {
      $timeDifference = $currentTimeStamp - $sf_response['lastServiceTime'];
      if ($timeDifference > $this->authTokenManager->getSfConfig('sf_verify_area.service_expiry')) {
        $this->areaVerification->verifyAreaCode($sf_response['from_postal_code']);
      }
    }
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_available_time.api_url_segment');
    if (!empty($auth_token)) {
      if (substr($endpoint_segment, 0, 1) !== '/') {
        $endpoint_segment = '/' . $endpoint_segment;
      }
      $endpoint = $this->state->get('sfUrl') . $endpoint_segment;
      $job_duration = $sf_response['job_duration'];
      $job_duration = str_replace([' hours', ' hour'], '', $job_duration);
      if (strpos($job_duration, "min") > 0 || strpos($job_duration, "Minutes") > 0 || strpos($job_duration, "minutes")) {
        $job_duration = 0.5;
      }
      $headers = [
        'Authorization' => $auth_token,
        'content-type' => 'application/json',
      ];
      $options += [
        "franchise_id" => $sf_response['franchise_id'],
        "brand" => $this->authTokenManager->getSfConfig('sf_brand.brand'),
        "service_type" => $this->authTokenManager->getSfConfig('sf_available_time.services_type'),
        "postal_code" => $sf_response['from_postal_code'],
        "service_id" => $sf_response['service_id'],
        "job_duration" => $job_duration,
      ];
      try {
        $res = $this->httpClient->request('POST', $endpoint, [
          'headers' => $headers,
          'json' => $options,
        ])->getBody();
        $result = Json::decode($res, TRUE);
        $this->loggerFactory->get('Salesforce - GetAvailableTimes')->notice(UrlHelper::buildQuery($options) . ' ' . Json::encode($result));
        return $result;
      }
      catch (RequestException $e) {
        $this->loggerFactory->get('Salesforce - GetAvailableTimes Fail')->error($e->getMessage());
      }
    }
  }

  /**
   * Get the time slots on the basis of start and end date form URL.
   */
  public function getTimesSlots() {
    $params = $this->request->getCurrentRequest();
    $start_date = $params->get('start_date');
    $end_date = $params->get('end_date');
    if ($start_date && $end_date) {
      $response = $this->getAvailableTimes(['start_date' => $start_date, 'end_date' => $end_date]);
      return new JsonResponse($response);
    }
    else {
      return FALSE;
    }
  }

}
