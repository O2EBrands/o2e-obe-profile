<?php

namespace Drupal\o2e_obe_common\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
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
      'o2e_obe_common.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'common_settings_form';
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
    $config = $this->config('o2e_obe_common.settings');
    $obe_common_state_data = $this->state->get('obe_common_data');
    $form['o2e_obe_common'] = [
      '#type' => 'fieldset',
    ];

    $form['o2e_obe_common']['brand'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand'),
      '#description' => $this->t('Select your choice of brand.'),
      '#options' => [
        'GJ NA' => $this->t('GJ NA'),
        'GJ AU' => $this->t('GJ AU'),
        'SSH' => $this->t('SSH'),
        'W1D' => $this->t('W1D'),
      ],
      '#size' => 4,
      '#default_value' => $obe_common_state_data['brand'] ?? $config->get('o2e_obe_common.brand'),
    ];
    $form['o2e_obe_common']['logo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logo URL'),
      '#description' => $this->t('Override the URL used for the site logo.'),
      '#default_value' => $obe_common_state_data['logo_url'] ?? $config->get('o2e_obe_common.logo_url'),
    ];
    $form['o2e_obe_common']['slot_holdtime_expiry_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Slot HoldTime Expiry Message'),
      '#description' => $this->t('Enter the message to be shown after Checktime Expiry.'),
      '#default_value' => $obe_common_state_data['slot_holdtime_expiry_message'] ?? $config->get('o2e_obe_common.slot_holdtime_expiry_message'),
      '#required' => TRUE,
    ];
    $form['o2e_obe_common']['booking_error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Booking Error Message'),
      '#description' => $this->t('Enter the message to be shown if booking cannot be done.'),
      '#default_value' => $obe_common_state_data['booking_error_message'] ?? $config->get('o2e_obe_common.booking_error_message'),
      '#required' => TRUE,
    ];
    $form['o2e_obe_common']['obe_confirmation_message'] = [
      '#type' => 'text_format',
      '#format' => "full_html",
      '#title' => $this->t('OBE Confirmation Message'),
      '#description' => $this->t('Enter the message to be shown with image.'),
      '#default_value' => $obe_common_state_data['obe_confirmation_message'] ?? $config->get('o2e_obe_common.obe_confirmation_message'),
      '#required' => TRUE,
    ];
    $form['o2e_obe_common']['slot_holdtime_empty_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Slot HoldTime Empty Message'),
      '#description' => $this->t('Enter the message to be shown after time slot empty.'),
      '#default_value' => $obe_common_state_data['slot_holdtime_empty_message'] ?? $config->get('o2e_obe_common.slot_holdtime_empty_message'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('o2e_obe_common.settings')
      ->set('o2e_obe_common.brand', $form_state->getValue('brand'))
      ->set('o2e_obe_common.logo_url', $form_state->getValue('logo_url'))
      ->set('o2e_obe_common.slot_holdtime_expiry_message', $form_state->getValue('slot_holdtime_expiry_message'))
      ->set('o2e_obe_common.slot_holdtime_empty_message', $form_state->getValue('slot_holdtime_empty_message'))
      ->set('o2e_obe_common.booking_error_message', $form_state->getValue('booking_error_message'))
      ->set('o2e_obe_common.obe_confirmation_message', $form_state->getValue('obe_confirmation_message')['value'])
      ->save();
    // Set confirm message in state to store the value.
    $this->state->set('obe_common_data', $this->config('o2e_obe_common.settings')->get('o2e_obe_common'));
  }

}
