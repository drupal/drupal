<?php

/**
 * @file
 * Definition of Drupal\field\FieldInstance.
 */

namespace Drupal\field;

/**
 * Class for field instance objects.
 */
class FieldInstance implements \ArrayAccess {

  /**
   * The instance definition, as read from configuration storage.
   *
   * @var array
   */
  public $definition;

  /**
   * The widget object used for this instance.
   *
   * @var Drupal\field\Plugin\Type\Widget\WidgetInterface
   */
  protected $widget;

  /**
   * The formatter objects used for this instance, keyed by view mode.
   *
   * @var array
   */
  protected $formatters;

  /**
   * Constructs a FieldInstance object.
   *
   * @param array $definition
   *   The instance definition array, as read from configuration storage.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * Returns the Widget plugin for the instance.
   *
   * @return Drupal\field\Plugin\Type\Widget\WidgetInterface
   *   The Widget plugin to be used for the instance.
   */
  public function getWidget() {
    if (empty($this->widget)) {
      $widget_properties = $this->definition['widget'];

      // Let modules alter the widget properties.
      $context = array(
        'entity_type' => $this->definition['entity_type'],
        'bundle' => $this->definition['bundle'],
        'field' => field_info_field($this->definition['field_name']),
        'instance' => $this,
      );
      drupal_alter(array('field_widget_properties', 'field_widget_properties_' . $this->definition['entity_type']), $widget_properties, $context);

      $options = array(
        'instance' => $this,
        'type' => $widget_properties['type'],
        'settings' => $widget_properties['settings'],
        'weight' => $widget_properties['weight'],
      );
      $this->widget = field_get_plugin_manager('widget')->getInstance($options);
    }

    return $this->widget;
  }

  /**
   * Returns a Formatter plugin for the instance.
   *
   * @param mixed $display_properties
   *   Can be either:
   *   - The name of a view mode.
   *   - An array of display properties, as found in the 'display' entry of
   *     $instance definitions.
   *
   * @return Drupal\field\Plugin\Type\Formatter\FormatterInterface|null
   *   The Formatter plugin to be used for the instance, or NULL if the field
   *   is hidden.
   */
  public function getFormatter($display_properties) {
    if (is_string($display_properties)) {
      // A view mode was provided. Switch to 'default' if the view mode is not
      // configured to use dedicated settings.
      $view_mode = $display_properties;
      $view_mode_settings = field_view_mode_settings($this->definition['entity_type'], $this->definition['bundle']);
      $actual_mode = (!empty($view_mode_settings[$view_mode]['custom_settings']) ? $view_mode : 'default');

      if (isset($this->formatters[$actual_mode])) {
        return $this->formatters[$actual_mode];
      }

      // Switch to 'hidden' if the instance has no properties for the view
      // mode.
      if (isset($this->definition['display'][$actual_mode])) {
        $display_properties = $this->definition['display'][$actual_mode];
      }
      else {
        $display_properties = array(
          'type' => 'hidden',
          'settings' => array(),
          'label' => 'above',
          'weight' => 0,
        );
      }

      // Let modules alter the widget properties.
      $context = array(
        'entity_type' => $this->definition['entity_type'],
        'bundle' => $this->definition['bundle'],
        'field' => field_info_field($this->definition['field_name']),
        'instance' => $this,
        'view_mode' => $view_mode,
      );
      drupal_alter(array('field_display', 'field_display_' . $this->definition['entity_type']), $display_properties, $context);
    }
    else {
      // Arbitrary display settings. Make sure defaults are present.
      $display_properties += array(
        'settings' => array(),
        'label' => 'above',
        'weight' => 0,
      );
      $view_mode = '_custom_display';
    }

    if (!empty($display_properties['type']) && $display_properties['type'] != 'hidden') {
      $options = array(
        'instance' => $this,
        'type' => $display_properties['type'],
        'settings' => $display_properties['settings'],
        'label' => $display_properties['label'],
        'weight' => $display_properties['weight'],
        'view_mode' => $view_mode,
      );
      $formatter = field_get_plugin_manager('formatter')->getInstance($options);
    }
    else {
      $formatter = NULL;
    }

    // Persist the object if we were not passed custom display settings.
    if (isset($actual_mode)) {
      $this->formatters[$actual_mode] = $formatter;
    }

    return $formatter;
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->definition[$offset]) || array_key_exists($offset, $this->definition);
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function &offsetGet($offset) {
    return $this->definition[$offset];
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    if (!isset($offset)) {
      // Do nothing; $array[] syntax is not supported by this temporary wrapper.
      return;
    }
    $this->definition[$offset] = $value;

    // If the widget or formatter properties changed, the corrsponding plugins
    // need to be re-instanciated.
    if ($offset == 'widget') {
      unset($this->widget);
    }
    if ($offset == 'display') {
      unset($this->formatters);
    }
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->definition[$offset]);

    // If the widget or formatter properties changed, the corrsponding plugins
    // need to be re-instanciated.
    if ($offset == 'widget') {
      unset($this->widget);
    }
    if ($offset == 'display') {
      unset($this->formatters);
    }
  }

  /**
   * Returns the instance definition as a regular array.
   *
   * This is used as a temporary BC layer.
   * @todo Remove once the external APIs have been converted to use
   *   FieldInstance objects.
   *
   * @return array
   *   The instance definition as a regular array.
   */
  public function getArray() {
    return $this->definition;
  }

  /**
   * Handles serialization of Drupal\field\FieldInstance objects.
   */
  public function __sleep() {
    return array('definition');
  }

}
