<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;
use Drupal\ckeditor5\HTMLRestrictions;

/**
 * CKEditor 5 Alignment plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Alignment extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The default configuration for this plugin.
   *
   * @var string[][]
   */
  const DEFAULT_CONFIGURATION = [
    'enabled_alignments' => [
      'left',
      'center',
      'right',
      'justify',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * {@inheritdoc}
   *
   * Form for choosing which alignment types are available.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled_alignments'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Alignments'),
      '#description' => $this->t('These are the alignment types that will appear in the alignment dropdown.'),
    ];

    foreach ($this->getPluginDefinition()->getCKEditor5Config()['alignment']['options'] as $alignment_option) {
      $name = $alignment_option['name'];
      $form['enabled_alignments'][$name] = [
        '#type' => 'checkbox',
        '#title' => $this->t($name),
        '#return_value' => $name,
        '#default_value' => in_array($name, $this->configuration['enabled_alignments'], TRUE) ? $name : NULL,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.ckeditor5_alignment.
    $form_value = $form_state->getValue('enabled_alignments');
    $config_value = array_values(array_filter($form_value));
    $form_state->setValue('enabled_alignments', $config_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled_alignments'] = $form_state->getValue('enabled_alignments');
  }

  /**
   * {@inheritdoc}
   *
   * Filters the alignment options to those chosen in editor config.
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $enabled_alignments = $this->configuration['enabled_alignments'];
    $all_alignment_options = $static_plugin_config['alignment']['options'];

    $configured_alignment_options = array_filter($all_alignment_options, function ($option) use ($enabled_alignments) {
      return in_array($option['name'], $enabled_alignments, TRUE);
    });

    return [
      'alignment' => [
        'options' => array_values($configured_alignment_options),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    $enabled_alignments = $this->configuration['enabled_alignments'];
    $plugin_definition = $this->getPluginDefinition();
    $all_elements = $plugin_definition->getElements();
    $subset = HTMLRestrictions::fromString(implode($all_elements));
    foreach ($plugin_definition->getCKEditor5Config()['alignment']['options'] as $configured_alignment) {
      if (!in_array($configured_alignment['name'], $enabled_alignments, TRUE)) {
        $element_string = '<$text-container class=' . '"' . $configured_alignment["className"] . '"' . '>';
        $subset = $subset->diff(HTMLRestrictions::fromString($element_string));
      }
    }
    return $subset->toCKEditor5ElementsArray();
  }

}
