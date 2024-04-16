<?php

namespace Drupal\file\Upload;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Resolves the file upload location from a file field definition.
 */
trait FileUploadLocationTrait {

  /**
   * Resolves the file upload location from a file field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The file field definition.
   *
   * @return string
   *   An un-sanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   */
  public function getUploadLocation(FieldDefinitionInterface $fieldDefinition): string {
    assert(is_a($fieldDefinition->getClass(), FileFieldItemList::class, TRUE));
    $fieldItemDataDefinition = FieldItemDataDefinition::create($fieldDefinition);
    $fileItem = new FileItem($fieldItemDataDefinition);
    return $fileItem->getUploadLocation();
  }

}
