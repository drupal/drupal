<?php

/**
 * @file
 * Contains \Drupal\comment\CommentFieldName.
 */

namespace Drupal\comment;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * The field item for the 'fieldname' field.
 */
class CommentFieldName extends StringItem {

  /**
   * Definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('String value'),
        'class' => '\Drupal\comment\CommentFieldNameValue',
        'computed' => TRUE,
      );
    }
    return static::$propertyDefinitions;
  }

}
