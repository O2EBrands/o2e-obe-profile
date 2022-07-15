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
    $test = \Drupal::service('o2e_obe_salesforce.client.api')->salesforceClientGet('VerifyAreaServiced', ['query' => ['from_postal_code' => '90211', 'brand' => '1-800-GOT-JUNK?']]);
    $config = $this->config('o2e_obe_salesforce.settings');
    $form['sf_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce API Information'),
      '#tree' => TRUE,
    ];
    $form['sf_auth']['login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URL'),
      '#default_value' => $config->get('sf_auth')['login_url'],
    ];
    $form['sf_auth']['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $config->get('sf_auth')['api_username'],
    ];
    $form['sf_auth']['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $config->get('sf_auth')['api_password'],
    ];
    $form['sf_auth']['grant_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Grant Type'),
      '#default_value' => $config->get('sf_auth')['grant_type'],
    ];
    $form['sf_auth']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client ID'),
      '#default_value' => $config->get('sf_auth')['client_id'],
    ];
    $form['sf_auth']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client Secret'),
      '#default_value' => $config->get('sf_auth')['client_secret'],
    ];
    $form['sf_verify_area'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce Verify Area Serviced information'),
      '#tree' => TRUE,
    ];
    $form['sf_verify_area']['brand'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brand'),
      '#default_value' => $config->get('sf_verify_area')['brand'],
    ];
    $form['sf_verify_area']['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expire Druration'),
      '#default_value' => !empty($config->get('sf_verify_area')['duration']) ? $config->get('sf_verify_area')['duration'] : '900',
    ];
    $form['sf_verify_area']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('sf_verify_area')['api_url_segment'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('o2e_obe_salesforce.settings')
      ->set('sf_auth', $form_state->getValue('sf_auth'))
      ->set('sf_verify_area', $form_state->getValue('sf_verify_area'))
      ->save();
  }

}
