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
use Drupal\o2e_obe_salesforce\AreaVerificationService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

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
<<<<<<< HEAD
=======
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
>>>>>>> WEB-4417: Update BookJobJunk API Integrations.
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
    $instance->bookJobService = $container->get('o2e_obe_salesforce.book_job_junk');
    $instance->messenger = $container->get('messenger');
    $instance->state = $container->get('state');
<<<<<<< HEAD
=======
    $instance->areaVerificationManager = $container->get('o2e_obe_salesforce.area_verification_service');
    $instance->timeService = $container->get('datetime.time');
>>>>>>> WEB-4417: Update BookJobJunk API Integrations.
    $instance->tempStoreFactory = $container->get('tempstore.private');
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
<<<<<<< HEAD
      $response = $this->bookJobService->bookJobJunk($query);
      if (!empty($response) && $response == 200) {
        $this->messenger()->addMessage($this->t('Booking done.'));
        return TRUE;
=======
      $bookJobCustom = $this->tempStoreFactory->get('o2e_obe_salesforce')->get('bookJobCustomer');
      $query+= $bookJobCustom;
      // Check Expiry.
      $currentTimeStamp = $this->timeService->getRequestTime();
      $checkExpiry = $this->areaVerificationManager->checkExpiry($currentTimeStamp);
      if ($checkExpiry) {
        $response = $this->bookJobService->bookJobJunk($query);
        if (!empty($response)) {
          $this->messenger()->addMessage($this->t('Booking done.'));
          return TRUE;
        }
        else {
          $formState->setErrorByName('', $this->t('We are unable to continue with the booking. Please Try Again'));
          return FALSE;
        }
>>>>>>> WEB-4417: Update BookJobJunk API Integrations.
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
        $modify_text = '<p>FULL PAYLOADS FOR DEBUGGING:</p><p>Book Job Junk Service Request: ' . $tempstore . '</p><p>Book Job Junk Service Result: '. Json::encode($response) . ' </p>';
          // @todo Optional: Alter message before it is sent.
        $modify_body = str_replace('[sf_failure_log]', $modify_text, $message['body']);
        $message['body'] = $modify_body;
        // Send message.
        $email_handler->sendMessage($webform_submission, $message);
        $formState->setErrorByName('', $this->t('We are unable to continue with the booking. Please Try Again'));
        return FALSE;
      }
      else {
        // Redirect to step 2
        $pages = $formState->get('pages');
        $this->goto_step('step2', $pages, $formState);
      }
    }
  }

  function goto_step($page, $pages, FormStateInterface $form_state) {
    // Convert associative array to index for easier manipulation.
    $all_keys = array_keys($pages);
    $goto_destination_page_index = array_search($page, $all_keys);
    if($goto_destination_page_index > 0){
      // The backend pointer for page will add 1 so to go our page we must -1.
      $form_state->set('current_page', $all_keys[$goto_destination_page_index-1]);
    }
  }
}
