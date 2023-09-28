<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * Request stack.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected $request;
  /**
   * Get the sfConfig values.
   *
   * @var \object|null
   */
  protected $ddConfig;

  /**
   * Constructor method.
   */
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, State $state, PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager, TimeInterface $time_service, RequestStack $request_stack, ConfigFactoryInterface $config) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->state = $state;
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;
    $this->timeService = $time_service;
    $this->request = $request_stack;
    $this->ddConfig = $config->get('o2e_obe_salesforce.datadog_settings');
  }

  /**
   * Holdtime method is hold the service id time.
   */
  public function create_lead(array $options = []) {
    // Variables for Datadog.
    $hostname = $this->request->getCurrentRequest()->getSchemeAndHttpHost();
    $dd_env = (!empty($_ENV["PANTHEON_ENVIRONMENT"])) ? 'env: ' . $_ENV["PANTHEON_ENVIRONMENT"] : '';
    $dd_api_key = $this->ddConfig->get('dd_config.api_key') ?? '';
    $datadog_url = $this->ddConfig->get('dd_config.api_url') ?? '';
    $dd_headers = [
      'Accept' => 'application/json',
      'Content-type' => 'application/json',
      'DD-API-KEY' => $dd_api_key,
    ];

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
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
    // Request object for Email entry.
    $request = UrlHelper::buildQuery($options);
    try {
      $startCreateLeadTimer = $this->timeService->getCurrentMicroTime();
      $response = $this->httpClient->request('POST', $api_url, [
        'headers' => $headers,
        'json' => $options,
      ]);
      $endCreateLeadTimer = $this->timeService->getCurrentMicroTime();
      // Logs the Timer CreateLead.
      $createLeadTimerDuration = round($endCreateLeadTimer - $startCreateLeadTimer, 2);
      $availabilityTimerDuration = "API response time: " . $createLeadTimerDuration;
      $zipCode = "Zip code: " . $tempstore['from_postal_code'];
      $userAgent = "User agent: " . $_SERVER['HTTP_USER_AGENT'];
      $this->obeSfLogger->log('Timer CreateLead', 'notice', $zipCode . " // " .
      $availabilityTimerDuration . " // " .
      $userAgent);

      $result = Json::decode($response->getBody(), TRUE);
      $data = UrlHelper::buildQuery($options) . ' ---- ' . $response->getStatusCode() . ' ---- ' . Json::encode($result);
      $this->obeSfLogger->log('Salesforce - Create Lead', 'notice', $data, [
        'request_url' => $api_url,
        'type' => 'POST',
        'payload' => $options,
        'response' => $result,
      ]);

      // Datadog Implementation.
      try {
        $this->httpClient->request('POST', $datadog_url, [
          'verify' => TRUE,
          'json' => [
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $zipCode . " // " .
              $availabilityTimerDuration . " // " .
              $userAgent,
              'service' => 'Timer - Create Lead',
            ],
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $data,
              'service' => 'Salesforce - Create Lead',
            ],
          ],
          'headers' => $dd_headers,
        ]);
      }
      catch (RequestException $e) {
      }
      // End of datadog implementation.
      // Tempstore to store createLead request log.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('createLead', [
        'name' => 'Create Lead',
        'url' => $api_url,
        'request' => $request,
        'response' => $result,
      ]);
      return $result;
    }
    catch (RequestException $e) {
      $this->obeSfLogger->log('Salesforce - Create Lead Fail', 'error', $e->getMessage());
      // Datadog Implementation.
      try {
        $this->httpClient->request('POST', $datadog_url, [
          'verify' => TRUE,
          'json' => [
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $e->getMessage(),
              'service' => 'Salesforce - Create Lead Fail',
              "status" => 'error',
            ],
          ],
          'headers' => $dd_headers,
        ]);
      }
      catch (RequestException $e) {
      }
      // End of datadog implementation.
      if (!empty($e->getResponse())) {
        // Tempstore to store createLead request log.
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('createLead', [
          'name' => 'Create Lead',
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
