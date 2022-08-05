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
 * Book Job Junk Service class is return the book Job details.
 */
class BookJobJunkService {


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
   * Return the book job junk data.
   */
  public function bookJobJunk(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_book_job_junk.api_url_segment');
    if (substr($endpoint_segment, 0, 1) !== '/') {
      $endpoint_segment = '/' . $endpoint_segment;
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');
    $api_url = $this->state->get('sfUrl') . $endpoint_segment;

    $headers = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options += [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'franchise_id' => $sf_response['franchise_id'],
      'customer_type' => $this->authTokenManager->getSfConfig('sf_book_job_junk_customer.customer_type'),
      'service_id' => $sf_response['service_id'],
    ];
    $tempstore->set('bookJobJunkService', $options);
    try {
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $result = Json::decode($response->getBody(), TRUE);
      $this->loggerFactory->get('Salesforce - Book Job Junk')->notice(UrlHelper::buildQuery($options) . ' ' . $response->getStatusCode());
      return $response->getStatusCode();
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Book Job Junk Fail')->error($e->getMessage());
      if (!empty($e->getResponse())) {
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse())
        ];
      }
    }
  }

}
