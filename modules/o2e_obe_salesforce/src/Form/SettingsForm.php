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
    $form['api']['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expire Druration'),
      '#default_value' => !empty($config->get('duration')) ? $config->get('duration') : '900',
    ];
    $form['api']['brand'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brand'),
      '#default_value' => $config->get('brand'),
    ];
    $form['api']['api_url_segment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api URL Segment'),
      '#default_value' => $config->get('api_url_segment'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('o2e_obe_salesforce.settings')
      ->set('login_url', $form_state->getValue('login_url'))
      ->set('api_username', $form_state->getValue('api_username'))
      ->set('api_password', $form_state->getValue('api_password'))
      ->set('grant_type', $form_state->getValue('grant_type'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('duration', $form_state->getValue('duration'))
      ->set('brand', $form_state->getValue('brand'))
      ->set('api_url_segment', $form_state->getValue('api_url_segment'))
      ->save();
  }

}
