<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'uuid' entity field type.
 *
 * The field uses a newly generated UUID as default value.
 */
#[FieldType(
  id: "uuid",
  label: new TranslatableMarkup("UUID"),
  description: new TranslatableMarkup("An entity field containing a UUID."),
  default_formatter: "string",
  no_ui: TRUE
)]
class UuidItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 128,
      'is_ascii' => TRUE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to one field item with a generated UUID.
    $uuid = \Drupal::service('uuid');
    $this->setValue(['value' => $uuid->generate()], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['unique keys']['value'] = ['value'];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = \Drupal::service('uuid')->generate();
    return $values;
  }

}
