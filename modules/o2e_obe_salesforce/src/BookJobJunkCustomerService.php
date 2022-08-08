<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Book Job Junk Customer Service class is return the book Job details.
 */
class BookJobJunkCustomerService {


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
   * Return the book job junk customer data.
   */
  public function bookJobJunkCustomer(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_book_job_junk_customer.api_url_segment');
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
    ];
    $tempstore->set('bookJobJunkCustomer', UrlHelper::buildQuery($options));
    try {
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $result = Json::decode($response->getBody(), TRUE);
      $this->loggerFactory->get('Salesforce - Book Job Junk Customer')->notice(UrlHelper::buildQuery($options) . ' ' . Json::encode($result));
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - Book Job Junk Fail Customer')->error($e->getMessage());
      if (!empty($e->getResponse())) {
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse())
        ];
      }
    }
  }

}
