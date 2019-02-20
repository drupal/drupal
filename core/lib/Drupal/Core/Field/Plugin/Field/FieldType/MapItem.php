<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'map' entity field type.
 *
 * @FieldType(
 *   id = "map",
 *   label = @Translation("Map"),
 *   description = @Translation("An entity field for storing a serialized array of values."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\MapFieldItemList",
 * )
 */
class MapItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // The properties are dynamic and can not be defined statically.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    // The default implementation of toArray() only returns known properties.
    // For a map, return everything as the properties are not pre-defined.
    return $this->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->values = [];
    if (!isset($values)) {
      return;
    }

    if (!is_array($values)) {
      if ($values instanceof MapItem) {
        $values = $values->getValue();
      }
      else {
        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
          $values = unserialize($values, ['allowed_classes' => FALSE]);
        }
        else {
          $values = unserialize($values);
        }
      }
    }

    $this->values = $values;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (!isset($this->values[$name])) {
      $this->values[$name] = [];
    }

    return $this->values[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    if (isset($value)) {
      $this->values[$name] = $value;
    }
    else {
      unset($this->values[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // A map item has no main property.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->values);
  }

}
