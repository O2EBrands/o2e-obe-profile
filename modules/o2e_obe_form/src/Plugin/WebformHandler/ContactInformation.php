<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\o2e_obe_salesforce\BookJobJunkCustomerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\o2e_obe_salesforce\PromoDetailsJunkService;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

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
class ContactInformation extends WebformHandlerBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->bookJobJunkService = $container->get('o2e_obe_salesforce.book_job_junk_customer');
    $instance->messenger = $container->get('messenger');
    $instance->promoCodeService = $container->get('o2e_obe_salesforce.promo_details_junk_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->validateZipCode($form_state);
  }

   /**
   * Validate phone.
   */
  private function validateZipCode(FormStateInterface $formState) {
    // $promotion_applied = FALSE;
    $current_page = $formState->get('current_page');
    if ($current_page === 'step3') {
      // Promo Code Verification (if applicable).
      $promocode = !empty($formState->getValue('promo_code')) ? $formState->getValue('promo_code') : NULL;
      if (!empty($promocode)) {
        $promo_response = $this->promoCodeService->getPromocode($promocode);
        if (!empty($promo_response)) {
          if (isset($promo_response['promotion_code'])) {
            // $promotion_applied = TRUE;
            $this->_bookJobJunkCustomer($formState);
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
            $this->_bookJobJunkCustomer($formState, $query);
          }
        }
        else {
          $this->messenger()->addMessage($this->t('We are unable to complete the booking now.'));
          return FALSE;
        }
      }
      else {
        $this->_bookJobJunkCustomer($formState);
      }
    }
  }

  function _bookJobJunkCustomer(FormStateInterface $formState, $promo_query = []) {
    $subdivisionRepository = new SubdivisionRepository();
    // Get contact details from form.
    $fname = !empty($formState->getValue('first_name')) ? Html::escape($formState->getValue('first_name')) : NULL;
    $lname = !empty($formState->getValue('last_name')) ? $formState->getValue('last_name') : NULL;
    $phone = !empty($formState->getValue('phone_number')) ? Html::escape($formState->getValue('phone_number')) : NULL;
    $email = !empty($formState->getValue('email')) ? Html::escape($formState->getValue('email')) : NULL;
    // Get Address filled.
    $address = !empty($formState->getValue('address')) ? $formState->getValue('address') : NULL;
    $country_code = \Drupal::state()->get('country_code');
    $state_code = $address['state_province'];
    $states = $subdivisionRepository->getList(['US']);
    $state = $states[$state_code];
    $to_address = $address['city'] . ';' . $address['country'] . ';' . $state . ';' . $address['address'] . ';' . $address['postal_code'];
    $start_date_time = '2022-07-28T12:30:00.000Z';
    $finish_date_time = '2022-07-30T12:30:00.000Z';
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getName();
    $language = ($current_language === 'English') ? $current_language : 'French';
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
    $response = $this->bookJobJunkService->bookJobJunkCustomer($query);

    if (!empty($response)) {
      if (isset($response['service_type_id'])) {
        return TRUE;
      }
      else {
        if (isset($response['code'])) {
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
            default:
              $this->messenger()->addMessage($response['message']);
              return FALSE;
          }
        }
      }
    }
    else {
      $this->messenger()->addMessage($this->t('We are unable to complete the booking now.'));
      return FALSE;
    }
  }
}
