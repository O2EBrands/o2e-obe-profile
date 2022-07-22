<?php

namespace Drupal\o2e_obe_salesforce;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Exception\RequestException;

/**
 * SalesforceClientApi class is create for handle the api requests.
 */
class AuthTokenManager {

  use StringTranslationTrait;

  /**
   * Bearer token required for subsequent API requests.
   *
   * @var string|null
   */
  protected $token = NULL;

  /**
   * Get the sfConfig values.
   *
   * @var \object|null
   */
  protected $sfConfig;

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
   * Constructor method.
   */
  public function __construct(ConfigFactoryInterface $config, Client $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state) {
    $this->sfConfig = $config->get('o2e_obe_salesforce.settings');
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->timeService = $time_service;
    $this->state = $state;

  }

  /**
   * GenerateToken the auth token.
   */
  public function generateToken() {
    if ($this->validateConfigField($this->sfConfig->get('sf_auth'))) {
      return [];
    }
    $currentTimeStamp = $this->timeService->getRequestTime();
    if ($this->token = $this->checkTokenValidity($currentTimeStamp)) {
      return $this;
    }
    $token_segemets = 'Bearer ';
    $endpoint = $this->sfConfig->get('sf_auth.login_url');
    $options = [
      'grant_type' => $this->sfConfig->get('sf_auth.grant_type'),
      'client_id' => $this->sfConfig->get('sf_auth.client_id'),
      'client_secret' => $this->sfConfig->get('sf_auth.client_secret'),
      'username' => $this->sfConfig->get('sf_auth.api_username'),
      'password' => $this->sfConfig->get('sf_auth.api_password'),
    ];
    try {
      // Get the access token first.
      $res = $this->httpClient->request('POST', $endpoint, ['form_params' => $options]);
      $result = Json::decode($res->getBody(), TRUE);
      $this->state->set('authtoken', $token_segemets . $result['access_token']);
      $this->state->set('sfUrl', $result['instance_url']);
      $this->state->set('lastAuthTime', $currentTimeStamp);
      $this->token = $token_segemets . $result['access_token'];
      return $this;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Token Fail')->error($e->getMessage());
    }
  }

  /**
   * Return the configuration.
   */
  public function getSfConfig($key) {
    return !empty($key) ? $this->sfConfig->get($key) : $this->sfConfig;
  }

  /**
   * Return the token.
   */
  public function getToken() {
    return $this->generateToken()->token;
  }

  /**
   * Check token validity.
   */
  public function checkTokenValidity($currentTimeStamp) {
    /* If last authentication was in last 5 hours (1800 seconds),
     * return session token, else authenticate again.
     */
    if (!empty($this->state->get('authtoken')) && $this->state->get('lastAuthTime')) {
      $timeDifference = $currentTimeStamp - $this->state->get('lastAuthTime');
      if ($timeDifference < $this->sfConfig->get('sf_auth.token_expiry')) {
        return $this->state->get('authtoken');
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * CheckAuthConfig method to if configuration details are correctly added.
   */
  public function validateConfigField($config) {
    if ($config) {
      foreach ($config as $key => $value) {
        if (empty($value)) {
          $message = $this->t(' @field field is required in the Salesforce configuration.', ['@field' => $key]);
          $this->loggerFactory->get('Salesforce - Api Fields ')->error($message);
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
