<?php

namespace Drupal\o2e_obe_salesforce;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\State\State;
use Drupal\o2e_obe_salesforce\AuthTokenManager;
use GuzzleHttp\Exception\RequestException;

/**
 * Get Payment Details Service class is return the payment details.
 */
class GetPaymentDetailsService {
    protected $httpClient;

    protected $loggerFactory;

    protected $timeService;

    protected $state;

    protected $authTokenManager;

    public function __construct(Client $http_client, LoggerChannelFactory $logger_factory, TimeInterface $time_service, State $state, AuthTokenManager $auth_token_manager) {
        $this->httpClient = $http_client;
        $this->loggerFactory = $logger_factory;
        $this->timeService = $time_service;
        $this->state = $state;
        $this->authTokenManager = $auth_token_manager;
    }

    /**
     * Fetch Payment details from salesforce.
     */
    public function GetPaymentDetails(string $quote_id) {
        $auth_token = $this->state->get('authtoken');
        $api_url = $this->authTokenManager->getSfConfig('sf_payment_details.api_url_segment');
        if (!str_starts_with($api_url, 'https://') && !str_starts_with($api_url, 'http://')) {
            if (!str_starts_with($api_url, '/')) {
                $api_url = $this->state->get('sfUrl') . '/' . $api_url;
            } else {
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
            $this->loggerFactory->get('Salesforce - Get Payment Details-response')->notice($response->getStatusCode());
            $this->loggerFactory->get('Salesforce - Get Payment Details')->notice(Json::encode($result));
            $this->loggerFactory->get('Salesforce - Timer GetPaymentDetails')->notice($firstAvailDateTimerDuration);
            $result['code'] = $response->getStatusCode();
            return $result;
        } catch (RequestException $e) {
            $this->loggerFactory->get('Salesforce - GetPaymentDetails Fail')->error($e->getMessage());
            if (!empty($e->getResponse())) {
                return [
                    'code' => $e->getCode(),
                    'message' => $e->getResponseBodySummary($e->getResponse()),
                ];
            }
        }
    }

}
