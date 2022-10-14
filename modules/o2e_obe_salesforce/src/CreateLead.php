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
 * CreateLead Servicee class is create the lead id in SF.  .
 */
class CreateLead {


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
   * Holdtime method is hold the service id time.
   */
  public function create_lead(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('create_lead.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }

    $headers = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options += [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
    ];
    try {
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $result = Json::decode($response->getBody(), TRUE);
      $this->loggerFactory->get('Salesforce - Create Lead')->notice(UrlHelper::buildQuery($options) . ' ' . $response->getStatusCode());
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Create Lead Fail')->error($e->getMessage());
    }
  }

}
