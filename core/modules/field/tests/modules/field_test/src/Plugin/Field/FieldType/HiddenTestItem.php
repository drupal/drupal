<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldType\HiddenTestItem.
 */

namespace Drupal\field_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'hidden_test' entity field item.
 *
 * @FieldType(
 *   id = "hidden_test_field",
 *   label = @Translation("Hidden from UI test field"),
 *   description = @Translation("Dummy hidden field type used for tests."),
 *   no_ui = TRUE,
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class HiddenTestItem extends TestItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Test integer value'));

    return $properties;
  }

}
