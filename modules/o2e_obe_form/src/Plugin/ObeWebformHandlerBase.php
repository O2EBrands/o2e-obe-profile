<?php

namespace Drupal\o2e_obe_form\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;

/**
 * Provides a base class for OBE Webform handler.
 */
abstract class ObeWebformHandlerBase extends WebformHandlerBase {

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform = NULL;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission = NULL;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);
    $allowed_fields = $wizard_steps = [];
    // Get all webform elements.
    $webform_fields = $this->getWebform()->getElementsInitializedAndFlattened();
    // Dynamically fetch the Steps and Fields of the webform.
    $allowed_webform_plugin_ids = [
      'textfield',
      'textarea',
      'hidden',
      'webform_address',
      'email',
      'tel',
      'checkboxes',
      'radios',
      'select',
      'checkbox',
      'webform_custom_composite',
      'number',
      'webform_radios_other',
      'value',
    ];
    $excluded_field_names = [
      'promo_code',
      'pick_up_date',
      'arrival_time',
      'service_id',
    ];
    foreach ($webform_fields as $field_array) {
      if (in_array($field_array['#webform_plugin_id'], $allowed_webform_plugin_ids)) {
        if (!in_array($field_array['#webform_key'], $excluded_field_names)) {
          $allowed_fields[$field_array['#webform_key']] = $field_array['#title'];
        }
      }
      if ($field_array['#webform_plugin_id'] === 'webform_wizard_page') {
        $wizard_steps[$field_array['#webform_key']] = $field_array['#title'];
      }
    }
    $form['wizard'] = [
      '#type' => 'details',
      '#title' => $this->t('Wizard Pages'),
    ];
    $form['wizard']['steps'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select the Wizard Page'),
      '#options' => $wizard_steps,
      '#required' => TRUE,
      '#default_value' => $this->configuration['steps'] ?? '',
    ];
    $form['redirect'] = [
      '#type' => 'details',
      '#title' => $this->t('Redirect To'),
    ];
    $form['redirect']['redirect_to_step'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select the Redirect To Page after Slot Expiry'),
      '#options' => $wizard_steps,
      '#required' => TRUE,
      '#default_value' => $this->configuration['redirect_to_step'] ?? '',
    ];
    $form['target_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Target Fields'),
    ];
    $form['target_fields']['handler_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the Fields for the Handler'),
      '#options' => $allowed_fields,
      '#default_value' => $this->configuration['handler_fields'] ?? [],
    ];
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
    $values = $form_state->getValues();

    // Cleanup wizard step.
    $wizards = array_values(array_filter($values['wizard']));
    foreach ($wizards as $step) {
      $this->configuration['steps'] = $step;
    }

    // Cleanup redirect to step.
    if (!empty($values['redirect'])) {
      $redirect = array_values(array_filter($values['redirect']));
      foreach ($redirect as $redirect_step) {
        $this->configuration['redirect_to_step'] = $redirect_step;
      }
    }

    // Cleanup target fields.
    if (!empty($values['target_fields'])) {
      $target_fields = array_values(array_filter($values['target_fields']));
      $fields = [];
      foreach ($target_fields as $configurations) {
        foreach ($configurations as $name => $value) {
          if (!empty($value)) {
            $fields[] = $name;
          }
        }
      }
    }
    $this->configuration['handler_fields'] = $fields;
  }

}
