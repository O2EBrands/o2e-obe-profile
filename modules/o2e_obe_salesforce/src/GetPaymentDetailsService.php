<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\State\State;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Get Payment Details Service class is return the payment details.
 */
class GetPaymentDetailsService {

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
   * Initialize service object.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state, AuthTokenManager $auth_token_manager, RequestStack $request_stack, ConfigFactoryInterface $config) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->timeService = $time_service;
    $this->state = $state;
    $this->authTokenManager = $auth_token_manager;
    $this->request = $request_stack;
    $this->ddConfig = $config->get('o2e_obe_salesforce.datadog_settings');
  }

  /**
   * Fetch Payment details from salesforce.
   */
  public function GetPaymentDetails(string $quote_id) {
    // Variables for Datadog.
    $hostname = $this->request->getCurrentRequest()->getSchemeAndHttpHost();
    $dd_env = 'env: ' . $_ENV["PANTHEON_ENVIRONMENT"];
    $dd_api_key = $this->ddConfig->get('dd_config.api_key') ?? '';
    $datadog_url = $this->ddConfig->get('dd_config.api_url') ?? '';
    $dd_headers = [
      'Accept' => 'application/json',
      'Content-type' => 'application/json',
      'DD-API-KEY' => $dd_api_key,
    ];

    $auth_token = $this->authTokenManager->getToken();
    $api_url = $this->authTokenManager->getSfConfig('sf_payment_details.api_url_segment');
    if (!str_starts_with($api_url, 'https://') && !str_starts_with($api_url, 'http://')) {
      if (!str_starts_with($api_url, '/')) {
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
    try {
      $startFirstAvailDateTimer = microtime(TRUE);
      $response = $this->httpClient->request('GET', $api_url, [
        'headers' => $headers,
        'query' => [
          'quoteId' => $quote_id,
        ],
      ]);
      $endFirstAvailDateTimer = microtime(TRUE);
      $firstAvailDateTimerDuration = round($endFirstAvailDateTimer - $startFirstAvailDateTimer, 2);
      $result['body'] = json_decode($response->getBody()->getContents(), TRUE);
      $result['code'] = $response->getStatusCode();
      $this->loggerFactory->get('Salesforce - Get Payment Details-response')->notice($result['code']);
      $this->loggerFactory->get('Salesforce - Get Payment Details')->notice(Json::encode($result));
      $this->loggerFactory->get('Salesforce - Timer GetPaymentDetails')->notice($firstAvailDateTimerDuration);
      // Datadog Implementation.
      try {
        $this->httpClient->request('POST', $datadog_url, [
          'verify' => TRUE,
          'json' => [
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $result['code'],
              'service' => 'Salesforce - Get Payment Details-response',
            ],
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => Json::encode($result),
              'service' => 'Salesforce - Get Payment Details',
            ],
            [
              'ddsource' => 'drupal',
              'ddtags' => $dd_env,
              'hostname' => $hostname,
              'message' => $firstAvailDateTimerDuration,
              'service' => 'Salesforce - Timer GetPaymentDetails',
            ],
          ],
          'headers' => $dd_headers,
        ]);
      }
      catch (RequestException $e) {
      }
      // End of datadog implementation.
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('Salesforce - GetPaymentDetails Fail')->error($e->getMessage());
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
              'service' => 'Salesforce - GetPaymentDetails Fail',
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
        return [
          'code' => $e->getCode(),
          'message' => $e->getResponseBodySummary($e->getResponse()),
        ];
      }
    }
  }

}
