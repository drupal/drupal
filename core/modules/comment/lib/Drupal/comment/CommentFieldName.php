<?php

/**
 * @file
 * Contains \Drupal\comment\CommentFieldName.
 */

namespace Drupal\comment;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\TypedData\DataDefinition;

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
      static::$propertyDefinitions['value'] = DataDefinition::create('string')
        ->setLabel(t('String value'))
        ->setClass('\Drupal\comment\CommentFieldNameValue')
        ->setComputed(TRUE);
    }
    return static::$propertyDefinitions;
  }

}
