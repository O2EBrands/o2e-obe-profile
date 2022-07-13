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
    $form['salesforce_authentication_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Salesforce Authentication Key'),
      '#description' => $this->t('Select the SF environment authentication key needed for this website.'),
      '#size' => 1,
      '#default_value' => 'Dev',
      '#weight' => '0',
    ];
    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce API Information'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['api']['login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URL'),
      '#default_value' => $config->get('login_url'),
    ];
    $form['api']['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $config->get('api_username'),
    ];
    $form['api']['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $config->get('api_password'),
    ];
    $form['api']['grant_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Grant Type'),
      '#default_value' => $config->get('grant_type'),
    ];
    $form['api']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client ID'),
      '#default_value' => $config->get('client_id'),
    ];
    $form['api']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OBE Client Secret'),
      '#default_value' => $config->get('client_secret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('o2e_obe_salesforce.settings')
      ->set('brand', $form_state->getValue('salesforce_authentication_key'))
      ->set('login_url', $form_state->getValue('login_url'))
      ->set('api_username', $form_state->getValue('api_username'))
      ->set('api_password', $form_state->getValue('api_password'))
      ->set('grant_type', $form_state->getValue('grant_type'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();
  }

}
