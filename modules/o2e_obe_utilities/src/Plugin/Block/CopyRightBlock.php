<?php

namespace Drupal\o2e_obe_utilities\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CopyRightBlock' block.
 *
 * @Block(
 *  id = "copy_right_block",
 *  admin_label = @Translation("Copy Right Block"),
 * )
 */
class CopyRightBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Implement create() method.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * Implement blockform() method.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Copyright statement text'),
      '#format' => "plain_text",
      '#description' => $this->t('@year : You can add this placeholder before, middle and after the string.'),
      '#default_value' => isset($config['description']) ? $config['description'] : '',
    ];
    return $form;
  }

  /**
   * Implement blockSubmit() method.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $description = $form_state->getValue('description')['value'];
    $year = date('Y');
    $description = str_replace('@year', $year, $description);
    $this->setConfigurationValue('description', $description);
  }

  /**
   * Implement build() method.
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#markup' => $config['description'],
    ];
  }

}
