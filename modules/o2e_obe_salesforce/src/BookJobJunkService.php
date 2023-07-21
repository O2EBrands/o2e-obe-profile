<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Datetime\TimeInterface;

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
   * Return the book job junk data.
   */
  public function bookJobJunk(array $options = []) {
    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_book_job_junk.api_url_segment');
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
      'service_id' => $sf_response['service_id'],
    ];
    $tempstore->set('bookJobJunkService', UrlHelper::buildQuery($options));
    // Request object for Email entry.
    $request = UrlHelper::buildQuery($options);
    try {
      $startBookJobTimer2 = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $endBookJobTimer2 = $this->timeService->getCurrentMicroTime();
      // Logs the Timer BookJobJunk2.
      $bookJobTimerDuration2 = round($endBookJobTimer2 - $startBookJobTimer2, 2);
      $availabilityTimerDuration = "API response time: " . $bookJobTimerDuration2;
      $zipCode = "Zip code: " . $sf_response['from_postal_code'];
      $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
      $this->obeSfLogger->log(
          'Timer BookJobJunk2', 'notice', $zipCode . " // " .
          $availabilityTimerDuration . " // " . $userAgent
      );

      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . ' ' . $response->getStatusCode();
      $this->obeSfLogger->log('Salesforce - BookJobJunk2', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options,
        'response' => $result,
      ]);
      // Tempstore to store bookjobservice request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('bookjobservice', [
        'name' => 'Book Job Junk Service',
        'url' => $api_url,
        'request' => $request,
        'response' => $result,
      ]);
      return $response->getStatusCode();
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - BookJobJunk2 Fail', 'error', $e->getMessage());
      if (!empty($e->getResponse())) {
        // Tempstore to store bookjobservice request log.
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('bookjobservice', [
          'name' => 'Book Job Junk Service',
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
