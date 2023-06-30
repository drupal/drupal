<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\entity_test\TypedData\ComputedString;

/**
 * Defines the 'Internal Property' entity test field type.
 *
 * @FieldType(
 *   id = "internal_property_test",
 *   label = @Translation("Internal Property (test)"),
 *   description = @Translation("A field containing one string, from which two strings are computed (one internal, one not)."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class InternalPropertyTestFieldItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add a computed property that is non-internal.
    $properties['non_internal_value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Computed string, non-internal property'))
      ->setComputed(TRUE)
      ->setClass(ComputedString::class)
      ->setInternal(FALSE);
    // Add a computed property that is internal.
    $properties['internal_value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Computed string, internal property'))
      ->setComputed(TRUE)
      ->setClass(ComputedString::class);
    return $properties;
  }

}
