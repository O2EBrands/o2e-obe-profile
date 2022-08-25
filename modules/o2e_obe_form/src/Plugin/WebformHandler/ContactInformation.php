<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\o2e_obe_form\Plugin\ObeWebformHandlerBase;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "o2e_obe_form_contact_validator",
 *   label = @Translation("Contact Information Validator"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ContactInformation extends ObeWebformHandlerBase {

  use StringTranslationTrait;

  /**
   * The Area Verification Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\BookJobJunkCustomerService
   */
  protected $bookJobJunkService;

  /**
   * The Address Manager.
   *
   * @var CommerceGuys\Addressing\Subdivision\SubdivisionRepository
   */
  protected $addressManager;

  /**
   * Messenger Object.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Promocode Verification Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\PromoDetailsJunkService
   */
  protected $promoCodeService;

  /**
   * State Manager.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The Area Verification Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\AreaVerificationService
   */
  protected $areaVerificationManager;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->bookJobJunkService = $container->get('o2e_obe_salesforce.book_job_junk_customer');
    $instance->promoCodeService = $container->get('o2e_obe_salesforce.promo_details_junk_service');
    $instance->messenger = $container->get('messenger');
    $instance->state = $container->get('state');
    $instance->languageManager = $container->get('language_manager');
    $instance->areaVerificationManager = $container->get('o2e_obe_salesforce.area_verification_service');
    $instance->timeService = $container->get('datetime.time');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->config = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $current_page = $form_state->get('current_page');
    $selected_step = $this->configuration['steps'];
    if ($current_page === $selected_step) {
      $selected_fields = $this->configuration['contact_fields'];
      $this->validateCustomer($form_state);
    }
  }

  /**
   * Custom function to integrate BookJunkJob1 API.
   */
  private function validateCustomer(FormStateInterface $formState) {
    // $current_page = $formState->get('current_page');
    // if ($current_page === 'step3') {
    // Promo Code Verification (if applicable).
    $promocode = !empty($formState->getValue('promo_code')) ? $formState->getValue('promo_code') : NULL;
    if (!empty($promocode)) {
      $promo_response = $this->promoCodeService->getPromocode($promocode);
      if (!empty($promo_response)) {
        if (isset($promo_response['promotion_code'])) {
          $query = [
            'promo_code' => $promo_response['promotion_code'],
            'additional_information_required' => FALSE,
          ];
          $this->bookJobJunkCustomer($formState, $query);
        }
        if (isset($promo_response['program_code'])) {
          $query = [
            'program_code' => $promo_response['program_code'],
            'nasa_program' => $promo_response['nasa_program'],
            'pricebook_id' => $promo_response['pricebook_id'],
            'loyalty_gift_card' => $promo_response['loyalty_gift_card'],
            'additional_information_required' => $promo_response['additional_information_required'],
            'additional_information' => $promo_response['additional_information'],
          ];
          $this->bookJobJunkCustomer($formState, $query);
        }
      }
      else {
        $formState->setErrorByName('promo_code', $this->t('Please enter correct Promo Code'));
        return FALSE;
      }
    }
    else {
      $this->bookJobJunkCustomer($formState);
    }
    // }
  }

  /**
   * Custom function to execute BookJobJunkCustomerService.
   */
  private function bookJobJunkCustomer(FormStateInterface $formState, $promo_query = []) {
    // Get contact details from form.
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('bookJobJunkCustomer');
    $fname = !empty($formState->getValue('first_name')) ? Html::escape($formState->getValue('first_name')) : NULL;
    $lname = !empty($formState->getValue('last_name')) ? $formState->getValue('last_name') : NULL;
    $phone = !empty($formState->getValue('phone_number')) ? Html::escape($formState->getValue('phone_number')) : NULL;
    $email = !empty($formState->getValue('email')) ? Html::escape($formState->getValue('email')) : NULL;

    // Get Address filled.
    $subdivisionRepository = new SubdivisionRepository();
    $address = !empty($formState->getValue('address')) ? $formState->getValue('address') : NULL;
    $country_code = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('country_code');
    $state_code = $address['state_province'];
    $states = $subdivisionRepository->getList([$country_code]);
    $state = $states[$state_code];
    $to_address = $address['city'] . ';' . $address['country'] . ';' . $state . ';' . $address['address'] . ';' . $address['postal_code'];

    // Get Start and End time.
    $start_date_time = $formState->getValue('start_date_time');
    $finish_date_time = $formState->getValue('finish_date_time');

    // Get current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getName();
    $language = ($current_language === 'English') ? $current_language : 'French';

    // Query parameter.
    $query = [
      'first_name' => $fname,
      'last_name' => $lname,
      'phone' => $phone,
      'email' => $email,
      'to_address' => $to_address,
      'start_date_time' => $start_date_time,
      'finish_date_time' => $finish_date_time,
      'language' => $language,
    ];
    if (!empty($promo_query)) {
      $query += $promo_query;
    }
    else {
      $query['additional_information_required'] = FALSE;
    }

    // Check Expiry.
    $currentTimeStamp = $this->timeService->getRequestTime();
    $checkExpiry = check_local_time_expiry($currentTimeStamp);
    if ($checkExpiry) {
      // Set the slotHoldTime tempstore to TRUE.
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('slotHoldTime', TRUE);
      $response = $this->bookJobJunkService->bookJobJunkCustomer($query);
      if (!empty($response)) {
        if (isset($response['service_type_id'])) {
          $general_data = [
            'first_name' => $fname,
            'phone' => $phone,
            'email' => $email,
            'to_address' => $to_address,
            'start_date_time' => $start_date_time,
            'finish_date_time' => $finish_date_time,
          ];
          $this->state->setMultiple($general_data);
          $this->tempStoreFactory->get('o2e_obe_salesforce')->set('bookJobCustomer', $response);
          return TRUE;
        }
        else {
          if (isset($response['code'])) {
            $form_object = $formState->getFormObject();
            $webform_submission = $form_object->getEntity();
            $webform = $webform_submission->getWebform();
            // Get email handler whose id matches the current page's id.
            $handlers = $webform->getHandlers();
            $email_handler = $handlers->get('book_junk_customer_failure');
            // Get message.
            $message = $email_handler->getMessage($webform_submission);
            $modify_text = '<p>FULL PAYLOADS FOR DEBUGGING:</p><p>Book Job Junk Customer Request: ' . $tempstore . '</p><p>Book Job Junk Customer Result: ' . Json::encode($response) . ' </p>';
            // @todo Optional: Alter message before it is sent.
            $modify_body = str_replace('[sf_failure_log]', $modify_text, $message['body']);
            $message['body'] = $modify_body;
            // Send message.
            $email_handler->sendMessage($webform_submission, $message);

            switch ($response['code']) {
              case 102:
                $formState->setErrorByName('first_name', $response['message']);
                break;

              case 103:
                $formState->setErrorByName('last_name', $response['message']);
                break;

              case 104:
                $formState->setErrorByName('phone', $response['message']);
                break;

              case 105:
                $formState->setErrorByName('email', $response['message']);
                break;

              case 106:
                $formState->setErrorByName('address][city', $response['message']);
                break;

              case 107:
                $formState->setErrorByName('address][country', $response['message']);
                break;

              case 108:
                $formState->setErrorByName('address][state_province', $response['message']);
                break;

              case 109:
                $formState->setErrorByName('address][address', $response['message']);
                break;

              case 110:
                $formState->setErrorByName('address][postal_code', $response['message']);
                break;

              case 113:
                $formState->setErrorByName('address][postal_code', $response['message']);
                break;

              case 404:
                $formState->setErrorByName('address][address', $this->t('Address is not correct.'));
                break;

              default:
                $formState->setErrorByName('', $this->t('Please enter correct data'));
                return FALSE;
            }
          }
        }
      }
      else {
        $booking_error_message = $this->config->get('o2e_obe_common.settings')->get('booking_error_message');
        $formState->setErrorByName('', $booking_error_message);
        return FALSE;
      }
    }
    else {
      // Redirect to step 2.
      $form['elements']['step2']['holdtime_data']['#access'] = FALSE;
      $this->tempStoreFactory->get('o2e_obe_salesforce')->set('slotHoldTime', FALSE);
      $pages = $formState->get('pages');
      goto_step('step2', $pages, $formState);
      // Show slot expiry message.
      $slot_expiry_message = $this->config->get('o2e_obe_common.settings')->get('slot_holdtime_expiry_message');
      $formState->setErrorByName('', $slot_expiry_message);
      return FALSE;
    }
  }

}
