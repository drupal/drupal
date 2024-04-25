<?php

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\ComputedFileUrl;

/**
 * File-specific plugin implementation of a URI item to provide a full URL.
 */
#[FieldType(
  id: "file_uri",
  label: new TranslatableMarkup("File URI"),
  description: new TranslatableMarkup("An entity field containing a file URI, and a computed root-relative file URL."),
  default_widget: "uri",
  default_formatter: "file_uri",
  no_ui: TRUE,
)]
class FileUriItem extends UriItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('Root-relative file URL'))
      ->setComputed(TRUE)
      ->setInternal(FALSE)
      ->setClass(ComputedFileUrl::class);

    return $properties;
  }

}
