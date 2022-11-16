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
  public function log(string $channel, string $severity, string $message, array $context = [], array $request_info = []) {
    $this->loggerFactory->get($channel)->log($severity, $message, $context);
  }

}
