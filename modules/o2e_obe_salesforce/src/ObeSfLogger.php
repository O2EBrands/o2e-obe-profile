<?php

namespace Drupal\o2e_obe_salesforce;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Defines a Class for logging Obe Sf Logger.
 */
class ObeSfLogger {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructor method.
   */
  public function __construct(LoggerChannelFactory $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * This function is calling for logging.
   */
  public function log(string $channel = NULL, string $error_type = NULL, string $error_msg = NULL, string $endpoint = NULL, string $endpointType = NULL, string $payload = NULL, string $result = NULL) {
    if ($error_type == 'error') {
      $this->loggerFactory->get($channel)->error($error_msg);
    }
    elseif ($error_type == 'notice') {
      $this->loggerFactory->get($channel)->notice($result);
    }
  }

}
