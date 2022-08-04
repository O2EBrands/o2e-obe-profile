<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\o2e_obe_salesforce\BookJobJunkService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->bookJobService = $container->get('o2e_obe_salesforce.book_job_junk');
    $instance->messenger = $container->get('messenger');
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
      $query = \Drupal::state()->getMultiple($general_data);
      $response = $this->bookJobJunkService->bookJobJunk($query);
      if (!empty($response)) {
        $this->messenger()->addMessage($this->t('Booking done.'));
        return TRUE;
      }
      else {
        $formState->setErrorByName('', $this->t('We are unable to continue with the booking. Please Try Again'));
        return FALSE;
      }
    }
  }
}
