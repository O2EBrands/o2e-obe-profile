<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;

/**
 * Hold Time Servicee class is hold the serviceid time .
 */
class HoldTimeService {


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
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;

  }

  /**
   * Holdtime method is hold the service id time.
   */
  public function holdtime(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_hold_time.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');

    $headers = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options += [
      'service_id' => $tempstore['service_id'],
    ];
    try {
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . '  -----  ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - Hold Time', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options['query'],
        'response' => $result,
      ]);
      return $response->getStatusCode();
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - Hold Time Fail', 'error', $e->getMessage(), NULL, NULL);
    }
  }

}
