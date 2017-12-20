<?php

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\ComputedFileUrl;

/**
 * File-specific plugin implementation of a URI item to provide a full URL.
 *
 * @FieldType(
 *   id = "file_uri",
 *   label = @Translation("File URI"),
 *   description = @Translation("An entity field containing a file URI, and a computed root-relative file URL."),
 *   no_ui = TRUE,
 *   default_formatter = "file_uri",
 *   default_widget = "uri",
 * )
 */
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
