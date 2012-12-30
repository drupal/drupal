<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterPluginManager..
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\field\Plugin\Type\Formatter\FormatterLegacyDiscoveryDecorator;
use Drupal\field\FieldInstance;

/**
 * Plugin type manager for field formatters.
 */
class FormatterPluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase:$defaults.
   */
  protected $defaults = array(
    'field_types' => array(),
    'settings' => array(),
  );

  /**
   * Constructs a FormatterPluginManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('field', 'formatter');
    $this->discovery = new FormatterLegacyDiscoveryDecorator($this->discovery);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'field_formatter_info');
    $this->discovery = new CacheDecorator($this->discovery, 'field_formatter_types', 'field');

    $this->factory = new FormatterFactory($this);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - instance: (FieldInstance) The field instance.
   *   - view_mode: (string) The view mode.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the formatter. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type, specified in
   *       hook_field_info(). The default formatter will also be used if the
   *       requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Each setting
   *       defaults to the default value specified in the formatter definition.
   *     - weight: (float) The weight to assign to the renderable element.
   *       Defaults to 0.
   *
   * @return \Drupal\field\Plugin\Type\Formatter\FormatterInterface
   *   A formatter object.
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    $instance = $options['instance'];
    $field = field_info_field($instance['field_name']);

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] == TRUE) {
      $configuration = $this->prepareConfiguration($field['type'], $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default formatter if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    $definition = $this->getDefinition($configuration['type']);
    if (!isset($definition['class']) || !in_array($field['type'], $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = field_info_field_types($field['type']);
      $plugin_id = $field_type_definition['default_formatter'];
    }

    $configuration += array(
      'instance' => $instance,
      'view_mode' => $options['view_mode'],
    );
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $properties
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += array(
      'label' => 'above',
      'settings' => array(),
      'weight' => 0,
    );
    // If no formatter is specified, use the default formatter.
    if (!isset($configuration['type'])) {
      $field_type = field_info_field_types($field_type);
      $configuration['type'] = $field_type['default_formatter'];
    }
    // Fill in default settings values for the formatter.
    $configuration['settings'] += field_info_formatter_settings($configuration['type']);

    return $configuration;
  }

}
