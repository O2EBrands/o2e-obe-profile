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
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['salesforce_authentication_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Salesforce Authentication Key'),
      '#description' => $this->t('Select the SF environment authentication key needed for this website.'),
      '#options' => ['Dev' => $this->t('Dev'), 'Test' => $this->t('Test'), 'Live' => $this->t('Live')],
      '#size' => 1,
      '#default_value' => 'Dev',
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Submit'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
  }

}
