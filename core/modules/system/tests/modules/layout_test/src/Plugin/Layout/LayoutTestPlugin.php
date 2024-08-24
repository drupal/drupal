<?php

declare(strict_types=1);

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The plugin that handles the default layout template.
 */
#[Layout(
  id: 'layout_test_plugin',
  label: new TranslatableMarkup('Layout plugin (with settings)'),
  category: new TranslatableMarkup('Layout test'),
  description: new TranslatableMarkup('Test layout'),
  template: "templates/layout-test-plugin",
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
  ],
)]
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
