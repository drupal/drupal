<?php

/**
 * @file
 * Contains \Drupal\comment\CommentNewItem.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\Plugin\DataType\IntegerItem;

/**
 * The field item for the 'new' field.
 */
class CommentNewItem extends IntegerItem {

  /**
   * Definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
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
        'label' => t('Integer value'),
        'class' => '\Drupal\comment\CommentNewValue',
        'computed' => TRUE,
      );
    }
    return static::$propertyDefinitions;
  }
}
