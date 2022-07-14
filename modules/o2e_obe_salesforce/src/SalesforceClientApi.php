<?php

namespace Drupal\o2e_obe_salesforce;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
    foreach ($config->get() as $key => $value) {
      if (empty($value) && $key != '_core') {
        $message = $this->t(' @field field is required in the Salesforce configuration.', ['@field' => $key]);
        $this->loggerFactory->get('Salesforce - Api Fields ')->error($message);
        return [];
      }
    }
    $currentTimeStamp = $this->timeService->getRequestTime();
    /* If last authentication was in last 15 min (900 seconds),
     * return session token, else authenticate again.
     */
    if (!empty($this->state->get('authToken')) && $this->state->get('lastAuthTime')) {
      $timeDifference = $currentTimeStamp - $this->state->get('lastAuthTime');

      if ($timeDifference < $config->get('duration')) {
        return $this->state->get('authtoken');
      }
    }
    $endpoint = $config->get('login_url');
    $options = [
      'grant_type' => $config->get('grant_type'),
      'client_id' => $config->get('client_id'),
      'client_secret' => $config->get('client_secret'),
      'username' => $config->get('api_username'),
      'password' => $config->get('api_password'),
    ];
    try {
      // Get the access token first.
      $res = $this->httpClient->request('POST', $endpoint, ['form_params' => $options]);
      $result = Json::decode($res->getBody(), TRUE);
      $this->state->set('authtoken', $result['access_token']);
      $this->state->set('sfUrl', $result['instance_url'] . $config->get('api_url_segment'));
      $this->state->set('lastAuthTime', $currentTimeStamp);
      return $result['access_token'];
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Token Fail')->error($e->getMessage());
    }
  }

  /**
   * Get request infomation.
   */
  public function salesforceClientGet($endpoint, $body, array $options = []) {
    $auth_token = $this->getAuthToken();
    $query = UrlHelper::buildQuery($body);
    $api_url = $this->state->get('sfUrl') . $endpoint . '?' . $query;
    $options['headers']['Authorization'] = 'Bearer ' . $auth_token;
    try {
      $result = $this->httpClient->request('GET', $api_url, $options);
      $result = Json::decode($result->getBody(), TRUE);
      $this->loggerFactory->get('Salesforce - VerifyAreaServiced')->notice($query .' '. Json::encode($result));
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - SalesforceClientGet Fail')->error($e->getMessage());
    }
  }

}
