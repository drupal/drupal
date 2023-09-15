<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\ByteSizeMarkup;

/**
 * Formatter that shows the file size in a human readable way.
 *
 * @FieldFormatter(
 *   id = "file_size",
 *   label = @Translation("File size"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class FileSize extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'filesize';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => ByteSizeMarkup::create((int) $item->value)];
    }

    return $elements;
  }

}
