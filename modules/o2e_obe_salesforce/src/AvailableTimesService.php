<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;

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
   * Get the time slots on the basis of start and end date.
   */
  public function getAvailableTimes(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_available_time.api_url_segment');
    if (!empty($auth_token)) {
      if (substr($endpoint_segment, 0, 1) !== '/') {
        $endpoint_segment = '/' . $endpoint_segment;
      }
      $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
      $endpoint = $this->state->get('sfUrl') . $endpoint_segment;
      $jobDuration = $tempstore['job_duration'];
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
        "franchise_id" => $tempstore['franchise_id'],
        "brand" => $this->authTokenManager->getSfConfig('sf_brand.brand'),
        "service_type" => $this->authTokenManager->getSfConfig('sf_available_time.services_type'),
        "postal_code" => $tempstore['from_postal_code'],
        "service_id" => $tempstore['service_id'],
        "job_duration" => $jobDuration,
      ];
      try {
        $res = $this->httpClient->request('POST', $endpoint, [
          'headers' => $headers,
          'json' => $options,
        ]);
        $result = Json::decode($res->getBody(), TRUE);
        $this->loggerFactory->get('Salesforce - GetAvailableTimes')->notice(UrlHelper::buildQuery($options) . ' ' . Json::encode($result));
        return $result;
      }
      catch (RequestException $e) {
        $this->loggerFactory->get('Salesforce - GetAvailableTimes Fail')->error($e->getMessage());
      }
    }
  }

}
