<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Markup;
use Drupal\o2e_obe_form\Plugin\ObeWebformHandlerBase;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "o2e_obe_form_zip_code_validator",
 *   label = @Translation("Zip Code Validator"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ZipCodeValidation extends ObeWebformHandlerBase {

  use StringTranslationTrait;

  /**
   * State Manager.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The Area Verification Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\AreaVerificationService
   */
  protected $areaVerificationManager;

  /**
   * The SalesForce Config values.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $salesforceConfig;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->salesforceConfig = $container->get('config.factory');
    $instance->areaVerificationManager = $container->get('o2e_obe_salesforce.area_verification_service');
    $instance->state = $container->get('state');
    $instance->timeService = $container->get('datetime.time');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['redirect']);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webform_submission) {
    $current_page = $formState->get('current_page');
    $selected_step = $this->configuration['steps'];
    if ($current_page === $selected_step) {
      $selected_fields = $this->configuration['handler_fields'];
      $query = [];
      foreach ($selected_fields as $field_name) {
        if (!empty($formState->getValue($field_name))) {
          $query[$field_name] = $formState->getValue($field_name);
        }
      }
      if (!empty($query)) {
        $response = $this->areaVerificationManager->verifyAreaCode($query);
        if (!empty($response)) {
          if (isset($response['service_id']) && isset($response['state'])) {
            $this->tempStoreFactory->get('o2e_obe_salesforce')->set('postalCodeData', [
              'state' => $response['state'],
              'zip_code' => $response['from_postal_code'],
              'job_duration' => $response['job_duration'],
              'drivetime_adjustment' => $response['drivetime_adjustment'],
            ]);
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('slotHoldTime');
            return TRUE;
          }
          elseif (isset($response['code']) && $response['code'] === 404) {
            if (strpos($response['message'], 'Area Not Serviced')) {
              $salesforceConfigData = $this->salesforceConfig->get('o2e_obe_salesforce.settings')->get('sf_verify_area');
              $enable_ans = $salesforceConfigData['enable_ans'];
              if ($enable_ans == TRUE) {
                $message = Markup::create($salesforceConfigData['ans_message']);
                $formState->setErrorByName('from_postal_code', $message);
              }
              else {
                $formState->setErrorByName('from_postal_code', $response['message']);
              }
            }
          }
          else {
            $formState->setErrorByName('from_postal_code', $response['message']);
          }
        }
        else {
          $booking_error_message = $this->salesforceConfig->get('o2e_obe_common.settings')->get('booking_error_message');
          $formState->setErrorByName('', $booking_error_message);
        }
      }
      else {
        $booking_error_message = $this->salesforceConfig->get('o2e_obe_common.settings')->get('booking_error_message');
        $formState->setErrorByName('', $booking_error_message);
        return FALSE;
      }
    }

  }

}
