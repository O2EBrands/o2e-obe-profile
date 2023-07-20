<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Datetime\TimeInterface;

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
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
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
    // Request object for Email entry.
    $request = UrlHelper::buildQuery($options);
    try {
      $startBookJobTimer = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $endBookJobTimer = $this->timeService->getCurrentMicroTime();
      // Logs the Timer BookJobJunk.
      $bookJobTimerDuration = round($endBookJobTimer - $startBookJobTimer, 2);
      $availabilityTimerDuration = "API response time: " . $bookJobTimerDuration;
      $zipCode = "Zip code: " . $sf_response['from_postal_code'];
      $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
      $this->obeSfLogger->log(
        'Timer BookJobJunk', 'notice', $zipCode . " // " .
        $availabilityTimerDuration . " // " . $userAgent
      );
      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . ' ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - BookJobJunk', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options,
        'response' => $result,
      ]);
      // Tempstore to store bookjobcustomer request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('bookjobcustomerjunk', [
        'name' => 'Book Job Junk Customer',
        'url' => $api_url,
        'request' => $request,
        'response' => $result,
      ]);
      return $result;
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - BookJobJunk Fail', 'error', $e->getMessage());
      if (!empty($e->getResponse())) {
        // Tempstore to store bookjobcustomer request log.
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('bookjobcustomerjunk', [
          'name' => 'Book Job Junk Customer',
          'url' => $api_url,
          'request' => $request,
          'response' => $e->getResponseBodySummary($e->getResponse()),
        ]);
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse()),
        ];
      }
    }
  }

}
