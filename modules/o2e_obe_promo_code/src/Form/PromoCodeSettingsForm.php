<?php

namespace Drupal\o2e_obe_promo_code\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class PromoCodeSettingsForm extends ConfigFormBase {

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
      'o2e_obe_promo_code.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'promo_code_settings_form';
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
    $config = $this->config('o2e_obe_promo_code.settings');
    $obe_promo_state_data = $this->state->get('obe_promo_data');
    $form['o2e_obe_promo_code']['sameday_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sameday promo notification status'),
      '#description' => $this->t('Activate or deactivate sameday booking promo notification.'),
      '#options' => [
        TRUE => $this->t('Active'),
        FALSE => $this->t('Not-Active'),
      ],
      '#default_value' => $obe_promo_state_data['sameday_status'] ?? $config->get('o2e_obe_promo_code.sameday_status'),
      '#required' => TRUE,
    ];
    $form['o2e_obe_promo_code']['sameday_details'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sameday promo details (max. 255 characters)'),
      '#description' => $this->t('Example: Save $13 on a same day service—enter promo code SAMEDAY13 in the next step to redeem.'),
      '#rows' => 1,
      '#maxlength' => 255,
      '#default_value' => $obe_promo_state_data['sameday_details'] ?? $config->get('o2e_obe_promo_code.sameday_details'),
    ];
    $form['o2e_obe_promo_code']['sameday_terms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Terms and conditions (max. 255 characters)'),
      '#description' => $this->t('Example: Valid for same day bookings only. No cash value. Cannot be combined with any other promotion. Not applicable to booked or scheduled jobs.'),
      '#rows' => 1,
      '#maxlength' => 255,
      '#default_value' => $obe_promo_state_data['sameday_terms'] ?? $config->get('o2e_obe_promo_code.sameday_terms'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('o2e_obe_promo_code.settings')
      ->set('o2e_obe_promo_code.sameday_status', $form_state->getValue('sameday_status'))
      ->set('o2e_obe_promo_code.sameday_details', $form_state->getValue('sameday_details'))
      ->set('o2e_obe_promo_code.sameday_terms', $form_state->getValue('sameday_terms'))
      ->save();

    // Set confirm message in state to store the value.
    $this->state->set('obe_promo_data', $this->config('o2e_obe_promo_code.settings')->get('o2e_obe_promo_code'));
  }

}
