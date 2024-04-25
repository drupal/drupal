<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\entity_test\TypedData\ComputedString;

/**
 * Defines the 'Single Internal Property' entity test field type.
 *
 * This is based off of the InternalPropertyTestFieldItem test field item type,
 * but only adds a single computed property. This tests that fields with a main
 * property name and one internal value are flattened.
 *
 * @see \Drupal\entity_test\Plugin\Field\FieldType\InternalPropertyTestFieldItem
 */
#[FieldType(
  id: "single_internal_property_test",
  label: new TranslatableMarkup("Single Internal Property (test)"),
  description: new TranslatableMarkup("A field containing one string, from which one internal string is computed."),
  default_widget: "string_textfield",
  default_formatter: "string",
)]
class SingleInternalPropertyTestFieldItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add a computed property that is internal.
    $properties['internal_value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Computed string, internal property'))
      ->setComputed(TRUE)
      ->setClass(ComputedString::class);
    return $properties;
  }

}
