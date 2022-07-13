<?php

namespace Drupal\o2e_obe_salesforce;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;

/**
 * SalesforceClientApi class.
 */
class SalesforceClientApi {

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
   * Constructor method.
   */
  public function __construct(ConfigFactoryInterface $config, Client $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state) {
    $this->config = $config;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->timeService = $time_service;
    $this->state = $state;

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
    );
  }

  /**
   * Get token infomation.
   */
  public function getAuthToken() {

    $currentTimeStamp = $this->timeService->getRequestTime();
    // If last authentication was in last 15 min (900 seconds), return session token, else authenticate again
    // dump($this->state->get('sfAuthenticationToken'));exit;.
    if (!empty($this->state->get('sfAuthenticationToken')) && $this->state->get('lastAuthenticationTimestamp')) {
      $timeDifference = $currentTimeStamp - $this->state->get('lastAuthenticationTimestamp');

      if ($timeDifference < 990) {
        return $this->state->get('sfAuthenticationToken');
      }
    }
    $config = $this->config->get('o2e_obe_salesforce.settings');
    $api_url = $config->get('login_url');
    $api_username = $config->get('api_username');
    $api_password = $config->get('api_password');
    $api_grant_type = $config->get('grant_type');
    $api_client_id = $config->get('client_id');
    $api_client_secret = $config->get('client_secret');
    $options = [
      'grant_type' => $api_grant_type,
      'client_id' => $api_client_id,
      'client_secret' => $api_client_secret,
      'username' => $api_username,
      'password' => $api_password,
    ];
    try {
      // Get the access token first.
      $res = $this->httpClient->request('POST', $api_url, ['form_params' => $options]);
      $result = json_decode($res->getBody(), TRUE);
      $this->state->set('sfAuthenticationToken', $result['access_token']);
      $this->state->set('sfObeApiUrl', $result['instance_url'] . '/services/apexrest/obe/');
      $this->state->set('lastAuthenticationTimestamp', $currentTimeStamp);
      return $result['access_token'];
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Token Fail')->error($e->getMessage());
    }
  }

  /**
   * Get request infomation.
   */
  public function salesforceClientGet($endpoint, $body, $options = []) {
    $auth_token = $this->getAuthToken();
    $query = http_build_query($body);
    $api_url = $this->state->get('sfObeApiUrl') . $endpoint . '?' . $query;
    $options['headers']['Authorization'] = 'Bearer ' . $auth_token;
    try {
      $res = $this->httpClient->request('GET', $api_url, $options);
      $result = json_decode($res->getBody(), TRUE);
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - SalesforceClientGet Fail')->error($e->getMessage());
    }
    return $authdata;
  }

}
