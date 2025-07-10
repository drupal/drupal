<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 List plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class ListPlugin extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'properties' => [
        'reversed' => TRUE,
        'startIndex' => TRUE,
        'styles' => TRUE,
      ],
      'multiBlock' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['reversed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the user to reverse an ordered list'),
      '#default_value' => $this->configuration['properties']['reversed'],
    ];
    $form['startIndex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the user to specify the start index of an ordered list'),
      '#default_value' => $this->configuration['properties']['startIndex'],
    ];
    $form['multiBlock'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the user to create paragraphs in list items (or other block elements)'),
      '#default_value' => $this->configuration['multiBlock'],
    ];
    $form['styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the user to choose a list style type'),
      '#description' => $this->t('Available list style types for ordered lists: letters and Roman numerals instead of only numbers. Available list style types for unordered lists: circles and squares instead of only discs.'),
      '#default_value' => $this->configuration['properties']['styles'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValue('reversed');
    $form_state->setValue('reversed', (bool) $form_value);
    $form_value = $form_state->getValue('startIndex');
    $form_state->setValue('startIndex', (bool) $form_value);
    $form_value = $form_state->getValue('styles');
    $form_state->setValue('styles', (bool) $form_value);
    $form_value = $form_state->getValue('multiBlock');
    $form_state->setValue('multiBlock', (bool) $form_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['properties']['reversed'] = $form_state->getValue('reversed');
    $this->configuration['properties']['startIndex'] = $form_state->getValue('startIndex');
    $this->configuration['properties']['styles'] = $form_state->getValue('styles');
    $this->configuration['multiBlock'] = $form_state->getValue('multiBlock');
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $static_plugin_config['list']['properties'] = $this->getConfiguration()['properties'];
    // Generate configuration to use `type` attribute-based list styles on <ul>
    // and <ol> elements.
    // @see https://ckeditor.com/docs/ckeditor5/latest/api/module_list_listconfig-ListPropertiesStyleConfig.html#member-useAttribute
    if ($this->getConfiguration()['properties']['styles']) {
      $static_plugin_config['list']['properties']['styles'] = ['useAttribute' => TRUE];
    }
    $static_plugin_config['list']['multiBlock'] = $this->getConfiguration()['multiBlock'];
    return $static_plugin_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    $subset = $this->getPluginDefinition()->getElements();
    if (!$this->getConfiguration()['properties']['styles']) {
      $subset = array_diff($subset, [
        '<ul type>',
        '<ol type>',
      ]);
    }
    $subset = array_diff($subset, ['<ol reversed start>']);
    $reversed_enabled = $this->getConfiguration()['properties']['reversed'];
    $start_index_enabled = $this->getConfiguration()['properties']['startIndex'];
    $subset[] = "<ol" . ($reversed_enabled ? ' reversed' : '') . ($start_index_enabled ? ' start' : '') . '>';
    return $subset;
  }

}
