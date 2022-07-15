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
    $config = $this->config->get('o2e_obe_salesforce.settings');
    $checkAuthConfig = $this->checkAuthConfig($config);
    if (empty($checkAuthConfig)) {
      return [];
    }
    $currentTimeStamp = $this->timeService->getRequestTime();
    /* If last authentication was in last 15 min (900 seconds),
     * return session token, else authenticate again.
     */
    if (!empty($this->state->get('authToken')) && $this->state->get('lastAuthTime')) {
      $timeDifference = $currentTimeStamp - $this->state->get('lastAuthTime');

      if ($timeDifference < $config->get('sf_verify_area')['duration']) {
        return $this->state->get('authtoken');
      }
    }
    $endpoint = $config->get('sf_auth')['login_url'];
    $options = [
      'grant_type' => $config->get('sf_auth')['grant_type'],
      'client_id' => $config->get('sf_auth')['client_id'],
      'client_secret' => $config->get('sf_auth')['client_secret'],
      'username' => $config->get('sf_auth')['api_username'],
      'password' => $config->get('sf_auth')['api_password'],
    ];
    try {
      // Get the access token first.
      $res = $this->httpClient->request('POST', $endpoint, ['form_params' => $options]);
      $result = Json::decode($res->getBody(), TRUE);
      $this->state->set('authtoken', $result['access_token']);
      $this->state->set('sfUrl', $result['instance_url'] . $config->get('sf_verify_area')['api_url_segment']);
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
  public function salesforceClientGet(string $endpoint, array $options = []) {
    $auth_token = $this->getAuthToken();
    if (!empty($auth_token)) {
      $api_url = $this->state->get('sfUrl') . $endpoint;
      $options['headers']['Authorization'] = 'Bearer ' . $auth_token;
      try {
        $result = $this->httpClient->request('GET', $api_url, $options);
        $result = Json::decode($result->getBody(), TRUE);
        $this->loggerFactory->get('Salesforce - VerifyAreaServiced')->notice($query . ' ' . Json::encode($result));
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
