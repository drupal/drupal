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

    // If the widget properties changed, the widget plugin needs to be
    // re-instanciated.
    if ($offset == 'widget') {
      unset($this->widget);
    }
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->definition[$offset]);

    // If the widget properties changed, the widget plugin needs to be
    // re-instanciated.
    if ($offset == 'widget') {
      unset($this->widget);
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
