<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\State\State;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Psr7\Response;
use Drupal\Component\Serialization\Json;

/**
 * Data Dog Service  class is return the book Job details.
 */
class DataDogService {

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
  public function __construct(Client $http_client, ObeSfLogger $obe_sf_logger, PrivateTempStoreFactory $temp_store_factory, RequestStack $request_stack, ConfigFactoryInterface $config) {
    $this->httpClient = $http_client;
    $this->obeSfLogger = $obe_sf_logger;
    $this->tempStoreFactory = $temp_store_factory;
    $this->request = $request_stack;
    $this->ddConfig = $config->get('o2e_obe_salesforce.datadog_settings');
  }

  /**
   * Create a success entry in datadog
   */
  public function createSuccessDatadog(string $api_name, string $request_method, string $api_url, Response $response, string $response_duration) {
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
		$datalog_msg = "";
		$tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');

		try {
				$ip_address_value = \Drupal::request()->getClientIp();
				$ip_addr = 'ip_address="' . $ip_address_value . '" '; 
				$zip = 'zip_code="' . $tempstore['from_postal_code'] . '" '; 
				$request_method = 'request.method="'. $request_method .'" ';
				$request_url = 'request.url="' . $api_url . '" '; 
				$response_http_status = 'response.http_status="' . $response->getStatusCode() . '" '; 
				$response_body = 'response.body="' . $response->getBody() . '" '; 
				$response_api_time = 'response.api_response_time="' . $response_duration . '" '; 
				$user_agent_info = 'user_agent="' . $_SERVER['HTTP_USER_AGENT'] . '" '; 
				$datalog_msg =  $ip_addr . $zip . $request_method . $request_url 
					. $response_http_status . $response_body . $response_api_time .  $user_agent_info;
				// $this->obeSfLogger->log('DataDog Log  - ' .  $api_name, 'notice', $datalog_msg);  

        $this->httpClient->request('POST', $datadog_url, [
					'verify' => TRUE,
					'json' => [
						[
							'ddsource' => 'drupal',
							'ddtags' => $dd_env,
							'hostname' => $hostname,
							'message' => $datalog_msg,
							'service' => $api_name,
						],
					],
					'headers' => $dd_headers,
				]);
      
			}
			catch (RequestException $e) {
			}
  }

  /**
   * Create a fail entry in datadog
   */
  public function createFailDatadog(string $api_name, string $request_method, string $api_url, RequestException $e = NULL, array $context = []) {
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
	$datalog_msg = "";
  $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('response');
	try {
    $ip_address_value = \Drupal::request()->getClientIp();
    $ip_addr = 'ip_address="' . $ip_address_value . '" '; 
    $zip = 'zip_code="' . $tempstore['from_postal_code'] . '" '; 
    $request_method = 'request.method="'. $request_method .'" ';
    $request_url = 'request.url="' . $api_url . '" '; 
    $user_agent_info = 'user_agent="' . $_SERVER['HTTP_USER_AGENT'] . '" '; 

		if (!empty($context)) {
			$request_error = $user_agent_info;
		}
		else {
			$response_http_status = 'response.http_status="' . $e->getCode() . '" '; 
			$response_body = 'response.body="' . $e->getResponseBodySummary($e->getResponse()) . '" '; 
			$request_error =  $response_http_status . $response_body;
		}
    $datalog_msg =  $ip_addr . $zip . $request_method . $request_url . $request_error .  $user_agent_info;
	//	$this->obeSfLogger->log('DataDog Log  - ' .  $api_name, 'notice', $datalog_msg); 

		$this->httpClient->request('POST', $datadog_url, [
				'verify' => TRUE,
				'json' => [
				[
					'ddsource' => 'drupal',
					'ddtags' => $dd_env,
					'hostname' => $hostname,
					'message' => $datalog_msg,
					'service' => $api_name,
					'status' => 'error',
			],
			],
			'headers' => $dd_headers,
		]);
		
	}
	catch (RequestException $e) {
	}
	// End of datadog implementation.
  }

}
