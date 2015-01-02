<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Field\FieldType\ShapeItemRequired.
 */

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the 'shape_required' field type.
 *
 * @FieldType(
 *   id = "shape_required",
 *   label = @Translation("Shape (required)"),
 *   description = @Translation("Yet another dummy field type."),
 * )
 */
class ShapeItemRequired extends ShapeItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['shape']->setRequired(TRUE);
    return $properties;
  }

}
