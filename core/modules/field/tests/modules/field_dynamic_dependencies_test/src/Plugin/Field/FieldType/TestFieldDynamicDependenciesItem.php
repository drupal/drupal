<?php

declare(strict_types=1);

namespace Drupal\field_dynamic_dependencies_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'test_field_dynamic_dependencies' field type.
 */
#[FieldType(
  id: 'test_field_dynamic_dependencies',
  label: new TranslatableMarkup('test_field_dynamic_dependencies'),
  description: new TranslatableMarkup('Some description'),
  default_widget: 'string_textfield',
  default_formatter: 'string',
)]
final class TestFieldDynamicDependenciesItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [];
  }

}
