<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\o2e_obe_form\Plugin\ObeWebformHandlerBase;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "o2e_obe_form_available_times_validator",
 *   label = @Translation("Available Times Validator"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class AvailableTimesVerification extends ObeWebformHandlerBase {

  use StringTranslationTrait;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * A config object for the OBE Common Form configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Messenger Object.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->timeService = $container->get('datetime.time');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->config = $container->get('config.factory');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webform_submission) {
    $current_page = $formState->get('current_page');
    $selected_step = $this->configuration['steps'];
    if ($current_page === $selected_step) {
      // Check Expiry.
      $checkExpiry = check_local_time_expiry();
      if ($checkExpiry) {
        // Set the slotHoldTime tempstore to TRUE.
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('slotHoldTime', TRUE);
        return TRUE;
      }
      else {
        // Redirect to selected redirected step.
        $form['elements'][$selected_step]['holdtime_data']['#access'] = FALSE;
        $this->tempStoreFactory->get('o2e_obe_salesforce')->set('slotHoldTime', FALSE);
        $pages = $formState->get('pages');
        $redirect_to_step = $this->configuration['redirect_to_step'];
        goto_step($redirect_to_step, $pages, $formState);
        // Show slot expiry message.
        $slot_expiry_message = $this->config->get('o2e_obe_common.settings')->get('slot_holdtime_expiry_message');
        $this->messenger()->addError($slot_expiry_message);
        return FALSE;
      }
    }
  }

}
