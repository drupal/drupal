<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Widget\WidgetPluginManager.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Plugin type manager for field widgets.
 */
class WidgetPluginManager extends PluginManagerBase {

  /**
   * An array of widget options for each field type.
   *
   * @var array
   */
  protected $widgetOptions;

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase:$defaults.
   */
  protected $defaults = array(
    'field_types' => array(),
    'settings' => array(),
    'multiple_values' => FALSE,
    'default_value' => TRUE,
  );

  /**
   * Constructs a WidgetPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('field/widget', $namespaces);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'field_widget_info');
    $this->discovery = new CacheDecorator($this->discovery, 'field_widget_types',  'field');

    $this->factory = new WidgetFactory($this->discovery);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - field_definition: (FieldDefinitionInterface) The field definition.
   *   - form_mode: (string) The form mode.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the widget. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - type: (string) The widget to use. Defaults to the
   *       'default_widget' for the field type, specified in
   *       hook_field_info(). The default widget will also be used if the
   *       requested widget is not available.
   *     - settings: (array) Settings specific to the widget. Each setting
   *       defaults to the default value specified in the widget definition.
   *
   * @return \Drupal\field\Plugin\Type\Widget\WidgetInterface
   *   A Widget object.
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    $field_definition = $options['field_definition'];
    $field_type = $field_definition->getFieldType();

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] == TRUE) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default widget if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    $definition = $this->getDefinition($configuration['type']);
    if (!isset($definition['class']) || !in_array($field_type, $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = field_info_field_types($field_type);
      $plugin_id = $field_type_definition['default_widget'];
    }

    $configuration += array(
      'field_definition' => $field_definition,
    );
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Merges default values for widget configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $configuration
   *   An array of widget configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += array(
      'settings' => array(),
    );
    // If no widget is specified, use the default widget.
    if (!isset($configuration['type'])) {
      $field_type = field_info_field_types($field_type);
      $configuration['type'] = $field_type['default_widget'];
    }
    // Fill in default settings values for the widget.
    $configuration['settings'] += field_info_widget_settings($configuration['type']);

    return $configuration;
  }

  /**
   * Returns an array of widget type options for a field type.
   *
   * @param string|null $field_type
   *   (optional) The name of a field type, or NULL to retrieve all widget
   *   options. Defaults to NULL.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all widget types,
   *   keyed by field type human name.
   */
  public function getOptions($field_type = NULL) {
    if (!isset($this->widgetOptions)) {
      $options = array();
      $field_types = field_info_field_types();
      $widget_types = $this->getDefinitions();
      uasort($widget_types, 'drupal_sort_weight');
      foreach ($widget_types as $name => $widget_type) {
        foreach ($widget_type['field_types'] as $widget_field_type) {
          // Check that the field type exists.
          if (isset($field_types[$widget_field_type])) {
            $options[$widget_field_type][$name] = $widget_type['label'];
          }
        }
      }
      $this->widgetOptions = $options;
    }
    if (isset($field_type)) {
      return !empty($this->widgetOptions[$field_type]) ? $this->widgetOptions[$field_type] : array();
    }

    return $this->widgetOptions;
  }

}
