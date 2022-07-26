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
use Drupal\o2e_obe_salesforce\AuthTokenManager;
use Drupal\o2e_obe_salesforce\AreaVerificationService;

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
   * Constructor method.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger_factory, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service, AreaVerificationService $area_verification) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
    $this->areaVerification = $area_verification;

  }

  /**
   * Get the time slots on the basis of start and end date.
   */
  public function getAvailableTimes(array $options = []) {
    $currentTimeStamp = $this->timeService->getRequestTime();
    $auth_token = $this->state->get('authtoken');
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    if($tempstore->get('lastavailabletime')) {
      $timeDifference = $currentTimeStamp - $tempstore->get('lastavailabletime');
      if($timeDifference < $this->authTokenManager->getSfConfig('sf_verify_area.service_expiry')) {
         $this->areaVerification->verifyAreaCode(['query' => [
          'from_postal_code' =>$tempstore->get('response')['from_postal_code'],
        ]]);
      }
    }
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_available_time.api_url_segment');
    if (!empty($auth_token)) {
      if (substr($endpoint_segment, 0, 1) !== '/') {
        $endpoint_segment = '/' . $endpoint_segment;
      }
      $endpoint = $this->state->get('sfUrl') . $endpoint_segment;
      $jobDuration = $tempstore->get('response')['job_duration'];
      $jobDuration = str_replace(" hours", "", $jobDuration);
      $jobDuration = str_replace(" hour", "", $jobDuration);
      if (strpos($jobDuration, "min") > 0 || strpos($jobDuration, "Minutes") > 0 || strpos($jobDuration, "minutes")) {
        $jobDuration = 0.5;
      }
      $headers = [
        'Authorization' => $auth_token,
        'content-type' => 'application/json',
      ];
      $options += [
        "franchise_id" => $tempstore->get('response')['franchise_id'],
        "brand" => $this->authTokenManager->getSfConfig('sf_brand.brand'),
        "service_type" => $this->authTokenManager->getSfConfig('sf_available_time.services_type'),
        "postal_code" => $tempstore->get('response')['from_postal_code'],
        "service_id" => $tempstore->get('response')['service_id'],
        "job_duration" => $jobDuration,
      ];
      try {
        $res = $this->httpClient->request('POST', $endpoint, [
          'headers' => $headers,
          'json' => $options,
        ]);
        $result = Json::decode($res->getBody(), TRUE);
        $tempstore->set('lastavailabletime', $currentTimeStamp);
        $this->loggerFactory->get('Salesforce - GetAvailableTimes')->notice(UrlHelper::buildQuery($options) . ' ' . Json::encode($result));
        return $result;
      }
      catch (RequestException $e) {
        $this->loggerFactory->get('Salesforce - GetAvailableTimes Fail')->error($e->getMessage());
      }
    }
  }

}
