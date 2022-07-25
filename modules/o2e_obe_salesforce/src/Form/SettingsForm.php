<?php

namespace Drupal\o2e_obe_salesforce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SettingsForm class creates the OBE Salesforce configuration form.
 *
 * This form is only accessible in the back-end.
 * 'salesforce_authentication_key' is a select to pick which SF API key will be used by the website.
 * 'submit' button is used to submit the form.
 */
class SettingsForm extends ConfigFormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('o2e_obe_salesforce.settings');
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
    $form['sf_available_time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce AvailableTimes Serviced Details'),
      '#tree' => TRUE,
    ];
    $form['sf_available_time']['services_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Services Type'),
      '#default_value' => $config->get('sf_available_time.services_type'),
    ];
    $form['sf_available_time']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_available_time.api_url_segment'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('o2e_obe_salesforce.settings')
      ->set('sf_available_time', $form_state->getValue('sf_available_time'))
      ->set('sf_brand', $form_state->getValue('sf_brand'))
      ->set('sf_auth', $form_state->getValue('sf_auth'))
      ->set('sf_verify_area', $form_state->getValue('sf_verify_area'))
      ->set('sf_available_time', $form_state->getValue('sf_available_time'))
      ->save();
  }

}
