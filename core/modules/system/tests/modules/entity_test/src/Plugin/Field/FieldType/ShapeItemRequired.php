<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'shape_required' field type.
 */
#[FieldType(
  id: "shape_required",
  label: new TranslatableMarkup("Shape (required)"),
  description: new TranslatableMarkup("Yet another dummy field type."),
)]
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
