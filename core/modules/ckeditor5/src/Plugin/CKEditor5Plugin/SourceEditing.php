<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Source Editing plugin configuration.
 *
 * @internal
 *   Plugin classes are internal.
 */
class SourceEditing extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['allowed_tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Manually editable HTML tags'),
      '#default_value' => implode(' ', $this->configuration['allowed_tags']),
      '#description' => $this->t('A list of HTML tags that can be used while editing source. It is only necessary to add tags that are not already supported by other enabled plugins. For example, if "Bold" is enabled, it is not necessary to add the <code>&lt;strong&gt;</code> tag, but it may be necessary to add <code>&lt;dl&gt;&lt;dt&gt;&lt;dd&gt;</code> in a format that does not have a definition list plugin, but requires definition list markup.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.ckeditor5_heading.
    $form_value = $form_state->getValue('allowed_tags');
    if (!is_array($form_value)) {
      $config_value = HTMLRestrictions::fromString($form_value)->toCKEditor5ElementsArray();
      $form_state->setValue('allowed_tags', $config_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['allowed_tags'] = $form_state->getValue('allowed_tags');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allowed_tags' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return $this->configuration['allowed_tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $restrictions = HTMLRestrictions::fromString(implode(' ', $this->configuration['allowed_tags']));
    return [
      'htmlSupport' => [
        'allow' => $restrictions->toGeneralHtmlSupportConfig(),
      ],
    ];
  }

}
