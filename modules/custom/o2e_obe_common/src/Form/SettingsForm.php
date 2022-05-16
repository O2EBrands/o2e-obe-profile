<?php

namespace Drupal\o2e_obe_common\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('o2e_obe_common.settings');
    $form['brand'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand'),
      '#description' => $this->t('Select your choice of brand.'),
      '#options' => ['GJ NA' => $this->t('GJ NA'), 'GJ AU' => $this->t('GJ AU'), 'SSH' => $this->t('SSH'), 'W1D' => $this->t('W1D')],
      '#size' => 4,
      '#default_value' => $config->get('brand'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('o2e_obe_common.settings')
      ->set('brand', $form_state->getValue('brand'))
      ->save();
  }

}
