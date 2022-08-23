<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\o2e_obe_salesforce\BookJobJunkService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\State;
use Drupal\Component\Serialization\Json;
use Drupal\o2e_obe_salesforce\AreaVerificationService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\o2e_obe_salesforce\HoldTimeService;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "o2e_obe_form_service_validator",
 *   label = @Translation("Service Validator"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class BookJobJunkServiceValidation extends WebformHandlerBase {

  use StringTranslationTrait;

  /**
   * The Area Verification Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\BookJobJunkService
   */
  protected $bookJobService;

  /**
   * Messenger Object.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
    $instance->bookJobService = $container->get('o2e_obe_salesforce.book_job_junk');
    $instance->messenger = $container->get('messenger');
    $instance->state = $container->get('state');
    $instance->areaVerificationManager = $container->get('o2e_obe_salesforce.area_verification_service');
    $instance->timeService = $container->get('datetime.time');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->holdTimeService = $container->get('o2e_obe_salesforce.hold_time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->validateService($form_state);
  }

  /**
   * Validate phone.
   */
  private function validateService(FormStateInterface $formState) {
    $tempstore = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('bookJobJunkService');
    $current_page = $formState->get('current_page');
    if ($current_page === 'step4') {
      // Get common parameters.
      $general_data = [
        'first_name',
        'phone',
        'email',
        'to_address',
        'start_date_time',
        'finish_date_time',
      ];
      $query = $this->state->getMultiple($general_data);
      $bookJobCustom = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('bookJobCustomer');
      $query += $bookJobCustom;
      // Check Expiry.
      $currentTimeStamp = $this->timeService->getRequestTime();
      $checkExpiry = check_local_time_expiry($currentTimeStamp);
      if ($checkExpiry) {
        // Registering service ID with HoldTimeAPI.
        $hold_time_query_parameters = [
          'start_date_time',
          'finish_date_time',
        ];
        $options = $this->state->getMultiple($hold_time_query_parameters);
        $holdTimeResponse = $this->holdTimeService->holdtime($options);
        $response = $this->bookJobService->bookJobJunk($query);
        if (!empty($response) && $response == 200) {
          $this->messenger()->addMessage($this->t('Booking done.'));
          return TRUE;
        }
        elseif (!empty($response) && $response > 200) {
          $form_object = $formState->getFormObject();
          $webform_submission = $form_object->getEntity();
          $webform = $webform_submission->getWebform();
          // Get email handler whose id matches the current page's id.
          $handlers = $webform->getHandlers();
          $email_handler = $handlers->get('book_junk_service_failure');
          // Get message.
          $message = $email_handler->getMessage($webform_submission);
          $modify_text = '<p>FULL PAYLOADS FOR DEBUGGING:</p><p>Book Job Junk Service Request: ' . $tempstore . '</p><p>Book Job Junk Service Result: ' . Json::encode($response) . ' </p>';
          // @todo Optional: Alter message before it is sent.
          $modify_body = str_replace('[sf_failure_log]', $modify_text, $message['body']);
          $message['body'] = $modify_body;
          // Send message.
          $email_handler->sendMessage($webform_submission, $message);
        }
        else {
          $formState->setErrorByName('', $this->t('We are unable to continue with the booking. Please Try Again'));
          return FALSE;
        }
      }
      else {
        // Redirect to step 2.
        $pages = $formState->get('pages');
        goto_step('step2', $pages, $formState);
        $formState->setErrorByName('', $this->t('We are unable to continue with the booking. Please Try Again'));
        return FALSE;
      }
    }
  }

}
