<?php

declare(strict_types=1);

namespace Drupal\settings_tray_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;

/**
 * @see \Drupal\settings_tray_test\Plugin\Block\SettingsTrayFormAnnotationIsClassBlock
 */
class SettingsTrayFormAnnotationIsClassBlockForm extends PluginFormBase {

  /**
   * The block plugin.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->plugin->buildConfigurationForm($form, $form_state);

    $form['some_setting'] = [
      '#type' => 'select',
      '#title' => t('Some setting'),
      '#options' => [
        'a' => 'A',
        'b' => 'B',
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
