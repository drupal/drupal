<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\FileFormatterBase.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;

/**
 * Base class for file formatters.
 */
abstract class FileFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    // Remove files specified to not be displayed.
    $fids = array();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if ($item->isDisplayed() && !empty($item->target_id)) {
          // Load the files from the files table.
          $fids[] = $item->target_id;
        }
      }
    }

    if ($fids) {
      $files = file_load_multiple($fids);

      foreach ($entities_items as $items) {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
        foreach ($items as $item) {
          // If the file does not exist, mark the entire item as empty.
          if (!empty($item->target_id) && !$item->hasNewEntity()) {
            $item->entity = isset($files[$item->target_id]) ? $files[$item->target_id] : NULL;
          }
        }
      }
    }
  }
}
