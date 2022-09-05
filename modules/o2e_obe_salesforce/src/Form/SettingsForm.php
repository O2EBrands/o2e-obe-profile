<?php

namespace Drupal\o2e_obe_salesforce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SettingsForm class creates the OBE Salesforce configuration form.
 *
 * This form is only accessible in the back-end.
 * 'salesforce_authentication_key' is a select to pick which SF API key will be used by the website.
 * 'submit' button is used to submit the form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The object State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'o2e_obe_salesforce.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salesforce_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('o2e_obe_salesforce.settings');
    $sf_state_data = $this->state->get('sf_data');
    $form['sf_brand'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Brand'),
      '#tree' => TRUE,
    ];
    $form['sf_brand']['brand'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brand'),
      '#default_value' => $config->get('sf_brand.brand'),
      '#required' => TRUE,
    ];
    $form['sf_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Oauth Details'),
      '#tree' => TRUE,
    ];
    $form['sf_auth']['login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URL'),
      '#default_value' => $config->get('sf_auth.login_url'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $config->get('sf_auth.api_username'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $config->get('sf_auth.api_password'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['grant_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Grant Type'),
      '#default_value' => $config->get('sf_auth.grant_type'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client ID'),
      '#default_value' => $config->get('sf_auth.client_id'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client Secret'),
      '#default_value' => $config->get('sf_auth.client_secret'),
      '#required' => TRUE,
    ];
    $form['sf_auth']['token_expiry'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token Expiry'),
      '#default_value' => !empty($config->get('sf_auth.token_expiry')) ? $config->get('sf_auth.token_expiry') : '18000',
      '#required' => TRUE,
    ];
    $form['sf_verify_area'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Verify Area Serviced Details'),
      '#tree' => TRUE,
    ];
    $form['sf_verify_area']['service_expiry'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Expiry'),
      '#default_value' => !empty($config->get('sf_verify_area.service_expiry')) ? $config->get('sf_verify_area.service_expiry') : '900',
      '#required' => TRUE,
    ];
    $form['sf_verify_area']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_verify_area.api_url_segment'),
      '#required' => TRUE,
    ];
    $form['sf_verify_area']['enable_ans'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ANS'),
      '#default_value' => $config->get('sf_verify_area.enable_ans'),
    ];
    $form['sf_verify_area']['ans_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ANS Message'),
      '#default_value' => $sf_state_data['ans_message'] ?? $config->get('sf_verify_area.ans_message'),
      '#states' => [
        'visible' => [
          ':input[name="sf_verify_area[enable_ans]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['sf_available_time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce AvailableTimes Serviced Details'),
      '#tree' => TRUE,
    ];
    $form['sf_available_time']['services_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Services Type'),
      '#default_value' => $config->get('sf_available_time.services_type'),
      '#required' => TRUE,
    ];
    $form['sf_available_time']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_available_time.api_url_segment'),
      '#required' => TRUE,
    ];
    $form['sf_available_time']['show_holdtime_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Slot Hold Time Message'),
      '#default_value' => $config->get('sf_available_time.show_holdtime_message'),
    ];
    $form['sf_available_time']['slot_holdtime_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Slot HoldTime Message'),
      '#default_value' => $sf_state_data['slot_holdtime_message'] ?? $config->get('sf_available_time.slot_holdtime_message'),
      '#states' => [
        'visible' => [
          ':input[name="sf_available_time[show_holdtime_message]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['sf_available_time']['slot_holdtime_sub_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Slot HoldTime Sub Message'),
      '#default_value' => $sf_state_data['slot_holdtime_sub_message'] ?? $config->get('sf_available_time.slot_holdtime_sub_message'),
      '#states' => [
        'visible' => [
          ':input[name="sf_available_time[show_holdtime_message]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['sf_promo_details_junk'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Promo Details Junk'),
      '#tree' => TRUE,
    ];
    $form['sf_promo_details_junk']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_promo_details_junk.api_url_segment'),
      '#required' => TRUE,
    ];
    $form['sf_book_job_junk_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Book Job Junk Customer'),
      '#tree' => TRUE,
    ];
    $form['sf_book_job_junk_customer']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_book_job_junk_customer.api_url_segment'),
      '#required' => TRUE,
    ];
    $form['sf_book_job_junk_customer']['customer_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Type'),
      '#default_value' => $config->get('sf_book_job_junk_customer.customer_type'),
      '#required' => TRUE,
    ];
    $form['sf_book_job_junk'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Book Job Junk'),
      '#tree' => TRUE,
    ];
    $form['sf_book_job_junk']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_book_job_junk.api_url_segment'),
      '#required' => TRUE,
    ];
    $form['sf_hold_time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Hold Time'),
      '#tree' => TRUE,
    ];
    $form['sf_hold_time']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_hold_time.api_url_segment'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('o2e_obe_salesforce.settings')
      ->set('sf_brand', $form_state->getValue('sf_brand'))
      ->set('sf_auth', $form_state->getValue('sf_auth'))
      ->set('sf_verify_area', $form_state->getValue('sf_verify_area'))
      ->set('sf_available_time', $form_state->getValue('sf_available_time'))
      ->set('sf_promo_details_junk', $form_state->getValue('sf_promo_details_junk'))
      ->set('sf_book_job_junk_customer', $form_state->getValue('sf_book_job_junk_customer'))
      ->set('sf_book_job_junk', $form_state->getValue('sf_book_job_junk'))
      ->set('sf_hold_time', $form_state->getValue('sf_hold_time'))
      ->save();
    // Salesforce config data to be stored in STATE.
    $sf_data = [
      'ans_message' => $form_state->getValue('sf_verify_area')['ans_message'],
      'slot_holdtime_message' => $form_state->getValue('sf_available_time')['slot_holdtime_message'],
      'slot_holdtime_sub_message' => $form_state->getValue('sf_available_time')['slot_holdtime_sub_message'],
    ];
    // Set confirm message in state to store the value.
    $this->state->set('sf_data', $sf_data);
  }

}
