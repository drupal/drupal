<?php

/**
 * @file
 * Contains \Drupal\field_test\Type\HiddenTestItem.
 */

namespace Drupal\field_test\Type;

/**
 * Defines the 'test_field' entity field item.
 */
class HiddenTestItem extends TestItem {

  /**
   * Property definitions of the contained properties.
   *
   * @see TestItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'integer',
        'label' => t('Test integer value'),
      );
    }
    return static::$propertyDefinitions;
  }

}
