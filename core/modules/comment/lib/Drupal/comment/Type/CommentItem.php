<?php

/**
 * @file
 * Contains \Drupal\comment\Type\CommentItem.
 */

namespace Drupal\comment\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'comment_field' entity field item.
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
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['status'] = array(
        'type' => 'integer',
        'label' => t('Comment status value'),
        'settings' => array('default_value' => COMMENT_OPEN),
      );
    }
    return static::$propertyDefinitions;
  }

}
