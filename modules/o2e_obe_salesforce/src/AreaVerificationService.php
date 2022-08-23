<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Area Verification Service class is return the area details.
 */
class AreaVerificationService {


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
   * The Auth Token Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\AuthTokenManager
   */
  protected $authTokenManager;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->timeService = $time_service;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;

  }

  /**
   * Verify the area on the basis of zip code.
   */
  public function verifyAreaCode(string $zipcode) {
    $options = [];
    $currentTimeStamp = $this->timeService->getRequestTime();
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->getSfConfig('sf_verify_area.api_url_segment');
    if (substr($endpoint_segment, 0, 1) !== '/') {
      $endpoint_segment = '/' . $endpoint_segment;
    }
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    $sf_response = $tempstore->get('response');
    $check_expiry = $this->checkExpiry($currentTimeStamp);
    if ($check_expiry && $sf_response['from_postal_code'] == $zipcode) {
      return $check_expiry;
    }
    $api_url = $this->state->get('sfUrl') . $endpoint_segment;

    $options['headers'] = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options['query'] = [
      'brand' => $this->authTokenManager->getSfConfig('sf_brand.brand'),
      'from_postal_code' => $zipcode,
    ];
    try {
      $response = $this->httpClient->request('GET', $api_url, $options);
      $result = Json::decode($response->getBody(), TRUE);
      $tempstore->set('response', [
        'service_id' => $result['service_id'],
        'from_postal_code' => $result['from_postal_code'],
        'franchise_id' => $result['franchise_id'],
        'job_duration' => $result['job_duration'],
        'lastServiceTime' => $currentTimeStamp,
        'state' => $result['state'],
      ]);

      $this->loggerFactory->get('Salesforce - VerifyAreaServiced')->notice(UrlHelper::buildQuery($options['query']) . ' ' . Json::encode($result));
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - VerifyAreaServiced Fail')->error($e->getMessage());
      if (!empty($e->getResponse())) {
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse())
        ];
      }
    }
  }

  /**
   * Check service id expiry.
   */
  public function checkExpiry($currentTimeStamp) {
    /* If last authentication was in last 15 min (900 seconds),
     * return area response, else call again.
     */
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
    if ($tempstore['lastServiceTime']) {
      $timeDifference = $currentTimeStamp - $tempstore['lastServiceTime'];
      if ($timeDifference < $this->authTokenManager->getSfConfig('sf_verify_area.service_expiry')) {
        return $tempstore;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

}
