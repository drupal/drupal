<?php

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * The plugin that handles the default layout template.
 *
 * @Layout(
 *   id = "layout_test_plugin",
 *   label = @Translation("Layout plugin (with settings)"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   template = "templates/layout-test-plugin",
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   }
 * )
 */
class LayoutTestPlugin extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'setting_1' => 'Default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['setting_1'] = [
      '#type' => 'textfield',
      '#title' => 'Blah',
      '#default_value' => $this->configuration['setting_1'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('setting_1') === 'Test Validation Error Message') {
      $form_state->setErrorByName('setting_1', 'Validation Error Message');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['setting_1'] = $form_state->getValue('setting_1');
  }

}
