<?php

namespace Drupal\o2e_obe_form\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\o2e_obe_salesforce\AreaVerificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\Render\Markup;

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
class ZipCodeValidation extends WebformHandlerBase {

  use StringTranslationTrait;

  /**
   * State Manager.
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
   * @var \object|null
   */
  protected $salesforceConfig;

  /**
   * Constructor method.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $salesforceConfig, AreaVerificationService $areaVerificationManager, State $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->salesforceConfig = $salesforceConfig;
    $this->areaVerificationManager = $areaVerificationManager;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('o2e_obe_salesforce.area_verification_service'),
      $container->get('state')
    );
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
    $current_page = $formState->get('current_page');
    if ($current_page === 'step1') {
      $zip_code = !empty($formState->getValue('zip_code')) ? Html::escape($formState->getValue('zip_code')) : NULL;
      // Skip empty field.
      if (empty($zip_code) || is_array($zip_code)) {
        return;
      }
      $response = $this->areaVerificationManager->verifyAreaCode($zip_code);
      if (!empty($response)) {
        if (isset($response['service_id']) && isset($response['state'])) {
          $this->state->set('state', $response['state']);
          $this->state->set('zip_code', $zip_code);
          return TRUE;
        }
        elseif (isset($response['code']) && $response['code'] === 404) {
          if (strpos('Area Not Serviced', $response['message'])) {
            $salesforceConfigData = $this->salesforceConfig->get('o2e_obe_salesforce.settings')->get('sf_verify_area');
            $enable_ans = $salesforceConfigData['enable_ans'];
            if ($enable_ans == TRUE) {
              $message = Markup::create($salesforceConfigData['ans_message']);
              $formState->setErrorByName('zip_code', $message);
            }
          }
        }
        else {
          $formState->setErrorByName('zip_code', $this->t('Please enter valid zip code.'));
        }
      }
      else {
        $formState->setErrorByName('zip_code', $this->t('Please enter valid zip code.'));
      }
    }
  }
}
