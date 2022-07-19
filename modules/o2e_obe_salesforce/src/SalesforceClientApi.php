<?php

namespace Drupal\o2e_obe_salesforce;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Exception\RequestException;

/**
 * SalesforceClientApi class is create for handle the api requests.
 */
class SalesforceClientApi {

  use StringTranslationTrait;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config Factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * The object State.
   *
   * @var useDrupal\Core\State\State
   */
  protected $state;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructor method.
   */
  public function __construct(ConfigFactoryInterface $config, Client $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state, PrivateTempStoreFactory $temp_store_factory) {
    $this->config = $config;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->timeService = $time_service;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;

  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('datetime.time'),
      $container->get('state'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * Get token infomation.
   */
  public function getAuthToken() {
    $config = $this->config->get('o2e_obe_salesforce.settings');
    $checkAuthConfig = $this->checkAuthConfig($config);
    if (empty($checkAuthConfig)) {
      return [];
    }
    $currentTimeStamp = $this->timeService->getRequestTime();
    /* If last authentication was in last 15 min (900 seconds),
     * return session token, else authenticate again.
     */
    if (!empty($this->state->get('authtoken')) && $this->state->get('lastAuthTime')) {
      $timeDifference = $currentTimeStamp - $this->state->get('lastAuthTime');
      if ($timeDifference < $config->get('sf_auth')['token_expiry']) {
        return $this->state->get('authtoken');
      }
    }
    $endpoint = $config->get('sf_auth.login_url');
    $options = [
      'grant_type' => $config->get('sf_auth.grant_type'),
      'client_id' => $config->get('sf_auth.client_id'),
      'client_secret' => $config->get('sf_auth.client_secret'),
      'username' => $config->get('sf_auth.api_username'),
      'password' => $config->get('sf_auth.api_password'),
    ];
    try {
      // Get the access token first.
      $res = $this->httpClient->request('POST', $endpoint, ['form_params' => $options]);
      $result = Json::decode($res->getBody(), TRUE);
      $this->state->set('authtoken', $result['access_token']);
      $this->state->set('sfUrl', $result['instance_url']);
      $this->state->set('lastAuthTime', $currentTimeStamp);
      return $result['access_token'];
    }
    catch (ClientException $e) {
      $this->loggerFactory->get('Salesforce - Token Fail')->error($e->getMessage());
    }
  }

  /**
   * Get request infomation.
   */
  public function verifyAreaRequest(array $options = []) {
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $currentTimeStamp = $this->timeService->getRequestTime();
    $config = $this->config->get('o2e_obe_salesforce.settings');
    if ($tempstore->get('response')['lastServiceTime']) {
      $timeDifference = $currentTimeStamp - $tempstore->get('response')['lastServiceTime'];
      if ($timeDifference < $config->get('sf_verify_area.service_expiry')) {
        return $tempstore->get('response');
      }
    }
    $auth_token = $this->getAuthToken();
    if (!empty($auth_token)) {
      if (substr($config->get('sf_verify_area.api_url_segment'), 0, 1) == '/') {
        $endpoint_segment = $config->get('sf_verify_area.api_url_segment');
      }
      else {
        $endpoint_segment = '/' . $config->get('sf_verify_area.api_url_segment');
      }
      $api_url = $this->state->get('sfUrl') . $endpoint_segment;
      $options['headers'] = [
        'Authorization' => 'Bearer ' . $auth_token,
        'content-type' => 'application/json',
      ];
      $options['query']['brand'] = $config->get('sf_brand.brand');
      try {
        $response = $this->httpClient->request('GET', $api_url, $options);
        $result = Json::decode($response->getBody(), TRUE);
        $tempstore->set('response', [
          'service_id' => $result['service_id'],
          'from_postal_code' => $result['from_postal_code'],
          'franchise_id' => $result['franchise_id'],
          'job_duration' => $result['job_duration'],
          'lastServiceTime' => $currentTimeStamp,
        ]);
        $this->loggerFactory->get('Salesforce - VerifyAreaServiced')->notice(UrlHelper::buildQuery($options['query']) . ' ' . Json::encode($result));
        return $result;
      }
      catch (ClientException $e) {
        $this->loggerFactory->get('Salesforce - SalesforceClientGet Fail')->error($e->getMessage());
      }
    }
    else {
      return [];
    }
  }

  /**
   * Get Available Times form the post request.
   */
  public function getAvailableTimes(array $options = []) {
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
    $config = $this->config->get('o2e_obe_salesforce.settings');
    $auth_token = $this->getAuthToken();
    if (!empty($auth_token)) {
      if (substr($config->get('sf_available_time.api_url_segment'), 0, 1) == '/') {
        $endpoint_segment = $config->get('sf_available_time.api_url_segment');
      }
      else {
        $endpoint_segment = '/' . $config->get('sf_available_time.api_url_segment');
      }
      $endpoint = $this->state->get('sfUrl') . $endpoint_segment;
      $jobDuration = $tempstore['job_duration'];
      $jobDuration = str_replace(" hours", "", $jobDuration);
      $jobDuration = str_replace(" hour", "", $jobDuration);
      if (strpos($jobDuration, "min") > 0 || strpos($jobDuration, "Minutes") > 0 || strpos($jobDuration, "minutes")) {
        $jobDuration = 0.5;
      }
      $headers = [
        'Authorization' => 'Bearer ' . $auth_token,
        'content-type' => 'application/json',
      ];
      $options += [
        "franchise_id" => $tempstore['franchise_id'],
        "brand" => $config->get('sf_brand.brand'),
        "service_type" => $config->get('sf_available_time.services_type'),
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

  /**
   * CheckAuthConfig method to if configuration details are correctly added.
   */
  public function checkAuthConfig($config) {
    $conifg_data = array_merge($config->get('sf_auth'), $config->get('sf_verify_area'));
    foreach ($conifg_data as $key => $value) {
      if (empty($value)) {
        $message = $this->t(' @field field is required in the Salesforce configuration.', ['@field' => $key]);
        $this->loggerFactory->get('Salesforce - Api Fields ')->error($message);
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
  }

}
