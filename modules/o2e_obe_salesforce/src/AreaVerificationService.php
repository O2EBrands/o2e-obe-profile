<?php

namespace Drupal\o2e_obe_salesforce;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Utility\UrlHelper;

/**
 * Area Verification Service class is return the area details.
 */
class AreaVerificationService extends AuthTokenManager {

  protected $tempStoreFactory;

  /**
   * The Auth Token Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\AuthTokenManager
   */
  protected $authTokenManager;

  /**
   * Constructor method.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, AuthTokenManager $auth_token_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->authTokenManager = $auth_token_manager;

  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('o2e_obe_salesforce.authtoken_manager'),
    );
  }

  /**
   * Verify the area on the basis of zip code.
   */
  public function getVarifyArea(array $options = []) {
    $currentTimeStamp = $this->authTokenManager->timeService->getRequestTime();
    $auth_token = $this->authTokenManager->getToken();
    $endpoint_segment = $this->authTokenManager->validateSlash($this->authTokenManager->sfConfig->get('sf_verify_area.api_url_segment'));
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    if ($result = $this->checkExpiry($currentTimeStamp)) {
      return $result;
    }
    $api_url = $this->authTokenManager->state->get('sfUrl') . $endpoint_segment;

    $options['headers'] = [
      'Authorization' => $auth_token,
      'content-type' => 'application/json',
    ];

    $options['query']['brand'] = $this->authTokenManager->sfConfig->get('sf_brand.brand');

    try {
      $response = $this->authTokenManager->httpClient->request('GET', $api_url, $options);
      $result = Json::decode($response->getBody(), TRUE);
      $tempstore->set('response', [
        'service_id' => $result['service_id'],
        'from_postal_code' => $result['from_postal_code'],
        'franchise_id' => $result['franchise_id'],
        'job_duration' => $result['job_duration'],
        'lastServiceTime' => $currentTimeStamp,
      ]);

      $this->authTokenManager->loggerFactory->get('Salesforce - VerifyAreaServiced')->notice(UrlHelper::buildQuery($options['query']) . ' ' . Json::encode($result));
      return $result;
    }
    catch (ClientException $e) {
      $this->authTokenManager->loggerFactory->get('Salesforce - VerifyAreaServiced Fail')->error($e->getMessage());
    }
  }

  /**
   * Check service id expiry.
   */
  public function checkExpiry($currentTimeStamp) {
    /* If last authentication was in last 15 min (900 seconds),
     * return area response, else call again.
     */
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce');
    if ($tempstore->get('response')['lastServiceTime']) {
      $timeDifference = $currentTimeStamp - $tempstore->get('response')['lastServiceTime'];
      if ($timeDifference < $this->authTokenManager->sfConfig->get('sf_verify_area.service_expiry')) {
        return $tempstore->get('response');
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
