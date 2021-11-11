<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Heading plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Heading extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The headings that cannot be disabled.
   *
   * @var string[]
   */
  const ALWAYS_ENABLED_HEADINGS = [
    'paragraph',
  ];

  /**
   * The default configuration for this plugin.
   *
   * @var string[][]
   */
  const DEFAULT_CONFIGURATION = [
    'enabled_headings' => [
      'heading2',
      'heading3',
      'heading4',
      'heading5',
      'heading6',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * Computes all valid choices for the "enabled_headings" setting.
   *
   * @see ckeditor5.schema.yml
   *
   * @return string[]
   *   All valid choices.
   */
  public static function validChoices(): array {
    $cke5_plugin_manager = \Drupal::service('plugin.manager.ckeditor5.plugin');
    assert($cke5_plugin_manager instanceof CKEditor5PluginManagerInterface);
    $plugin_definition = $cke5_plugin_manager->getDefinition('ckeditor5_heading');
    assert($plugin_definition->getClass() === static::class);
    return array_diff(
      array_column($plugin_definition->getCKEditor5Config()['heading']['options'], 'model'),
      static::ALWAYS_ENABLED_HEADINGS
    );
  }

  /**
   * Gets all enabled headings.
   *
   * @return string[]
   *   The values in the plugins.ckeditor5_heading.enabled_headings configuration
   *   plus the headings that are always enabled.
   */
  private function getEnabledHeadings(): array {
    return array_merge(
      self::ALWAYS_ENABLED_HEADINGS,
      $this->configuration['enabled_headings']
    );
  }

  /**
   * {@inheritdoc}
   *
   * Form for choosing which heading tags are available.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled_headings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Headings'),
      '#description' => $this->t('These are the headings that will appear in the headings dropdown. If a heading is not chosen here, it does not necessarily mean the corresponding tag is disallowed in the text format.'),
    ];

    foreach ($this->getPluginDefinition()->getCKEditor5Config()['heading']['options'] as $heading_option) {
      $model = $heading_option['model'];

      if (in_array($model, self::ALWAYS_ENABLED_HEADINGS, TRUE)) {
        continue;
      }

      // It's safe to use $model as a key: listing the same model twice with
      // different properties triggers a schema error in CKEditor 5.
      // @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/support/error-codes.html#error-schema-cannot-register-item-twice
      // @see https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#configuring-custom-heading-elements
      $form['enabled_headings'][$model] = self::generateCheckboxForHeadingOption($heading_option);
      $form['enabled_headings'][$model]['#default_value'] = in_array($model, $this->configuration['enabled_headings'], TRUE) ? $model : NULL;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.ckeditor5_heading.
    $form_value = $form_state->getValue('enabled_headings');
    $config_value = array_values(array_filter($form_value));
    $form_state->setValue('enabled_headings', $config_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled_headings'] = $form_state->getValue('enabled_headings');
  }

  /**
   * Generates checkbox for a CKEditor 5 heading plugin config option.
   *
   * @param array $heading_option
   *   A heading option configuration as the CKEditor 5 Heading plugin expects
   *   in its configuration.
   *
   * @return array
   *   The checkbox render array.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/api/module_heading_heading-HeadingConfig.html#member-options
   */
  private static function generateCheckboxForHeadingOption(array $heading_option): array {
    // This requires the `title` and `model` properties. The `class` property is
    // optional. The `view` property is not used.
    assert(array_key_exists('title', $heading_option));
    assert(array_key_exists('model', $heading_option));

    $checkbox = [
      '#type' => 'checkbox',
      '#title' => $heading_option['title'],
      '#return_value' => $heading_option['model'],
    ];
    if (isset($heading_option['class'])) {
      $checkbox['#label_attributes']['class'][] = $heading_option['class'];
      $checkbox['#label_attributes']['class'][] = 'ck';
    }

    return $checkbox;
  }

  /**
   * {@inheritdoc}
   *
   * Filters the header options to those chosen in editor config.
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $enabled_headings = $this->getEnabledHeadings($editor);
    $all_heading_options = $static_plugin_config['heading']['options'];

    $configured_heading_options = array_filter($all_heading_options, function ($option) use ($enabled_headings) {
      return in_array($option['model'], $enabled_headings, TRUE);
    });

    return [
      'heading' => [
        'options' => array_values($configured_heading_options),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return $this->enabledHeadingsToTags($this->configuration['enabled_headings']);
  }

  /**
   * Returns an array of enabled tags based on the enabled headings.
   *
   * @param string[] $enabled_headings
   *   Array of the enabled headings.
   *
   * @return string[]
   *   List of tags provided by the enabled headings.
   */
  private function enabledHeadingsToTags(array $enabled_headings): array {
    $plugin_definition = $this->getPluginDefinition();
    $elements = $plugin_definition->getElements();
    $heading_keyed_by_model = [];
    foreach ($plugin_definition->getCKEditor5Config()['heading']['options'] as $configured_heading) {
      if (isset($configured_heading['model'])) {
        $heading_keyed_by_model[$configured_heading['model']] = $configured_heading;
      }
    }

    $tags_to_return = [];
    foreach ($enabled_headings as $model) {
      if (isset($heading_keyed_by_model[$model]) && isset($heading_keyed_by_model[$model]['view'])) {
        $element_as_tag = "<{$heading_keyed_by_model[$model]['view']}>";
        if (in_array($element_as_tag, $elements, TRUE)) {
          $tags_to_return[] = "<{$heading_keyed_by_model[$model]['view']}>";
        }
      }
    }

    return $tags_to_return;
  }

}
