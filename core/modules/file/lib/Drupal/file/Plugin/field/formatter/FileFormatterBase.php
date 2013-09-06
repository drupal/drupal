<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\FileFormatterBase.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Base class for file formatters.
 */
abstract class FileFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities, $langcode, array $items) {
    // Remove files specified to not be displayed.
    $fids = array();
    foreach ($entities as $id => $entity) {
      foreach ($items[$id] as $item) {
        if ($this->isDisplayed($item) && !empty($item->target_id)) {
          // Load the files from the files table.
          $fids[] = $item->target_id;
        }
      }
    }

    if ($fids) {
      $files = file_load_multiple($fids);

      foreach ($entities as $id => $entity) {
        foreach ($items[$id] as $item) {
          // If the file does not exist, mark the entire item as empty.
          if (!empty($item->target_id)) {
            $item->entity = isset($files[$item->target_id]) ? $files[$item->target_id] : NULL;
          }
        }
      }
    }
  }

  /**
   * Determines whether a file should be displayed when outputting field content.
   *
   * @param $item
   *   A field item array.
   * @param $field
   *   A field array.
   *
   * @return
   *   Boolean TRUE if the file will be displayed, FALSE if the file is hidden.
   */
  protected function isDisplayed($item) {
    $settings = $this->getFieldSettings();
    if (!empty($settings['display_field'])) {
      return (bool) $item->display;
    }
    return TRUE;
  }
}
