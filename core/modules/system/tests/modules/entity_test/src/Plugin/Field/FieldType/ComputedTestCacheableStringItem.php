<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'string' entity field type with cacheability metadata.
 */
#[FieldType(
  id: "computed_test_cacheable_string_item",
  label: new TranslatableMarkup("Test Text (plain string with cacheability)"),
  description: new TranslatableMarkup("A test field containing a plain string value and cacheability metadata."),
  default_widget: "string_textfield",
  default_formatter: "string",
  no_ui: TRUE
)]
class ComputedTestCacheableStringItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('computed_test_cacheable_string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

}
