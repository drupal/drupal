<?php

/**
 * @file
 * Contains \Drupal\comment\Type\CommentItem.
 */

namespace Drupal\comment\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'test_field' entity field item.
 */
class CommentItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see CommentItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['status'] = array(
        'type' => 'integer',
        'label' => t('Comment status value'),
      );
    }
    return static::$propertyDefinitions;
  }

}
