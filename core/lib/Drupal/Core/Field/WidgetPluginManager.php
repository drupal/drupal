<?php

/**
 * @file
 * Contains \Drupal\Core\Field\WidgetPluginManager.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for field widgets.
 */
class WidgetPluginManager extends DefaultPluginManager {

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * An array of widget options for each field type.
   *
   * @var array
   */
  protected $widgetOptions;

  /**
   * Constructs a WidgetPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LanguageManager $language_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct('Plugin/Field/FieldWidget', $namespaces, $module_handler, 'Drupal\Core\Field\Annotation\FieldWidget');

    $this->setCacheBackend($cache_backend, $language_manager, 'field_widget_types_plugins');
    $this->alterInfo('field_widget_info');

    $this->factory = new WidgetFactory($this);
    $this->fieldTypeManager = $field_type_manager;
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
   *       'default_widget' for the field type. The default widget will also be
   *       used if the requested widget is not available.
   *     - settings: (array) Settings specific to the widget. Each setting
   *       defaults to the default value specified in the widget definition.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   A Widget object.
   */
  public function getInstance(array $options) {
    // Fill in defaults for missing properties.
    $options += array(
      'configuration' => array(),
      'prepare' => TRUE,
    );

    $configuration = $options['configuration'];
    $field_definition = $options['field_definition'];
    $field_type = $field_definition->getType();

    // Fill in default configuration if needed.
    if ($options['prepare']) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default widget if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    $definition = $this->getDefinition($configuration['type']);
    if (!isset($definition['class']) || !in_array($field_type, $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = $this->fieldTypeManager->getDefinition($field_type);
      $plugin_id = $field_type_definition['default_widget'];
    }

    $configuration += array(
      'field_definition' => $field_definition,
    );
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings']);
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
      $field_type = $this->fieldTypeManager->getDefinition($field_type);
      $configuration['type'] = $field_type['default_widget'];
    }
    // Fill in default settings values for the widget.
    $configuration['settings'] += $this->getDefaultSettings($configuration['type']);

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
      $field_types = $this->fieldTypeManager->getDefinitions();
      $widget_types = $this->getDefinitions();
      uasort($widget_types, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
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

  /**
   * Returns the default settings of a field widget.
   *
   * @param string $type
   *   A field widget type name.
   *
   * @return array
   *   The widget type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }

}
