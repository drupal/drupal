<?php

/**
 * @file
 * Contains \Drupal\comment\CommentFieldNameItem.
 */

namespace Drupal\comment;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * The field item for the 'fieldname' field.
 */
class CommentFieldNameItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('String value'))
      ->setClass('\Drupal\comment\CommentFieldNameValue')
      ->setComputed(TRUE);

    return $properties;
  }

}
