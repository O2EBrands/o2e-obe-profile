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
   * HoldTimeService Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\HoldTimeService
   */
  protected $holdTimeService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->timeService = $container->get('datetime.time');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->config = $container->get('config.factory');
    $instance->messenger = $container->get('messenger');
    $instance->holdTimeService = $container->get('o2e_obe_salesforce.hold_time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['target_fields']);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webform_submission) {
    $current_page = $formState->get('current_page');
    $selected_step = $this->configuration['steps'];
    $sfresponse = $this->tempStoreFactory->get('o2e_obe_salesforce');
    if ($current_page === $selected_step) {
      $hotd_time_success = $sfresponse->get('slotHoldTimesuccess');
      $start_form_date = $formState->getValue('start_date_time');
      $end_form_date = $formState->getValue('finish_date_time');
      $holdSlotTime = $sfresponse->get('holdSlotTime');
      if ($hotd_time_success == TRUE && (isset($holdSlotTime['start_date_time']) && isset($holdSlotTime['start_date_time'])) && ($holdSlotTime['start_date_time'] == $start_form_date && $holdSlotTime['finish_date_time'] == $end_form_date)) {
        // Check Expiry.
        $checkExpiry = check_local_time_expiry();
        if ($checkExpiry) {
          $sfresponse->set('slotHoldTime', TRUE);
          return TRUE;
        }
        else {
          $this->display_expiry_message($form, $formState, $sfresponse);
        }
      }
      else {
        $options = [
          'start_date_time' => $start_form_date,
          'finish_date_time' => $end_form_date,
        ];
        $holdTimeResponse = $this->holdTimeService->holdtime($options);
        if ($holdTimeResponse == TRUE) {
          $sfresponse->set('holdSlotTime', $options);
          $sfresponse->set('slotHoldTimesuccess', TRUE);
          $sfresponse->set('slotHoldTime', TRUE);

        }
        else {
          $sfresponse->delete('holdSlotTime');
          $this->display_expiry_message($form, $formState, $sfresponse);
        }
      }

    }
  }

  /**
  * Redirect to selected redirected step.
  */
  private function display_expiry_message(array &$form, FormStateInterface $formState, $sfresponse) {
    $form['elements'][$selected_step]['holdtime_data']['#access'] = FALSE;
    $sfresponse->set('slotHoldTime', FALSE);
    $sfresponse->set('slotHoldTimesuccess', FALSE);
    $pages = $formState->get('pages');
    $redirect_to_step = $this->configuration['redirect_to_step'];
    goto_step($redirect_to_step, $pages, $formState);
    // Show slot expiry message.
    $slot_expiry_message = $this->config->get('o2e_obe_common.settings')->get('o2e_obe_common')['slot_holdtime_expiry_message'];
    $this->messenger()->addError($slot_expiry_message);
    return FALSE;
  }
}

