<?php

/**
 * @file
 * Contains \Drupal\field_test\Type\TestItem.
 */

namespace Drupal\field_test\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'test_field' entity field item.
 */
class TestItem extends LegacyConfigFieldItem {

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
