<?php

namespace Drupal\o2e_obe_salesforce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SettingsForm class creates the Datadog configuration form.
 */
class DatadogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'o2e_obe_salesforce.datadog_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'datadog_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('o2e_obe_salesforce.datadog_settings');
    $form['dd_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Datadog Configurations'),
      '#tree' => TRUE,
    ];
    $form['dd_config']['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('dd_config.api_url'),
      '#required' => TRUE,
    ];
    $form['dd_config']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('dd_config.api_key'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('o2e_obe_salesforce.datadog_settings')
      ->set('dd_config', $form_state->getValue('dd_config'))
      ->save();
  }

}
