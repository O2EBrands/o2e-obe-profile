<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
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
   * Return the book job junk customer data.
   */
  public function bookJobJunkCustomer(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_book_job_junk_customer.api_url_segment');
    if (strpos($api_url, 'https://') !== 0 && strpos($api_url, 'http://') !== 0) {
      if (substr($api_url, 0, 1) !== '/') {
        $api_url = $this->state->get('sfUrl') . '/' . $api_url;
      }
      else {
        $api_url = $this->state->get('sfUrl') . $api_url;
      }
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');

    $headers = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];
    $options += [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'franchise_id' => $sf_response['franchise_id'],
    ];
    $tempstore->set('bookJobJunkCustomer', UrlHelper::buildQuery($options));
    try {
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . ' ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - Book Job Junk Customer', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options['query'],
        'response' => $result,
      ]);
      return $result;
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - Book Job Junk Fail Customer', 'error', $e->getMessage());
      if (!empty($e->getResponse())) {
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse()),
        ];
      }
    }
  }

}
