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
    unset($elements['target_fields']);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webform_submission) {
    $current_page = $formState->get('current_page');
    $selected_step = $this->configuration['steps'];
    if ($current_page === $selected_step) {
      $zip_code = !empty($formState->getValue('from_postal_code')) ? $formState->getValue('from_postal_code') : NULL;
      $booking_error_message = $this->salesforceConfig->get('o2e_obe_common.settings')->get('o2e_obe_common')['booking_error_message'];
      // Skip empty field.
      if (!empty($zip_code)) {
        $zip_code = str_replace(' ', '', $zip_code);
        $postalData = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('postalCodeData');
        if (!empty($postalData) && $postalData['zip_code'] !== $zip_code) {
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('response');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('postalCodeData');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('currentLocalTime');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('lead_id');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('slotHoldTimesuccess');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('bookJobJunkService');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('bookJobJunkCustomer');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('bookJobCustomer');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('country_code');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('holdSlotTime');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('slotHoldTime');
          $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('ans_zip');
        }
        $response = $this->areaVerificationManager->verifyAreaCode($zip_code);
        if (!empty($response)) {
          if (isset($response['service_id'])) {
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('ans_zip');
            $this->tempStoreFactory->get('o2e_obe_salesforce')->set('postalCodeData', [
              'state' => $response['state'],
              'zip_code' => $response['from_postal_code'],
              'job_duration' => $response['job_duration'],
              'drivetime_adjustment' => $response['drivetime_adjustment'] ?? '',
              'franchise_id' => $response['franchise_id'],
              'franchise_name' => $response['franchise_name'],
              'geolocation' => $response['geolocation'] ?? '',
            ]);
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('slotHoldTime');
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('ans_zip');
            return TRUE;
          }
          elseif (isset($response['code']) && $response['code'] === 404) {
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('response');
            if (strpos($response['message'], 'Area Not Serviced')) {
              $this->tempStoreFactory->get('o2e_obe_salesforce')->set('ans_zip', $zip_code);
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
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('response');
            if (!empty($response['message'])) {
              $formState->setErrorByName('from_postal_code', $response['message']);
            }
            else {
              $formState->setErrorByName('', $booking_error_message);
            }
            $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('ans_zip');
          }
        }
      }
      else {
        $formState->setErrorByName('', $this->t('Invalid ZIP/Postal code format'));
        $this->tempStoreFactory->get('o2e_obe_salesforce')->delete('ans_zip');
        return FALSE;
      }
    }

  }

}
