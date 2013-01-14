<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelectorInterface.
 */

namespace Drupal\edit;

use Drupal\field\FieldInstance;

/**
 * Interface for selecting an in-place editor (an Editor plugin) for a field.
 */
interface EditorSelectorInterface {

  /**
   * Returns the in-place editor (an Editor plugin) to use for a field.
   *
   * @param string $formatter_type
   *   The field's formatter type name.
   * @param \Drupal\field\FieldInstance $instance
   *   The field's instance info.
   * @param array $items
   *   The field's item values.
   *
   * @return string|NULL
   *   The editor to use, or NULL to not enable in-place editing.
   */
  public function getEditor($formatter_type, FieldInstance $instance, array $items);

  /**
   * Returns the attachments for all editors.
   *
   * @return array
   *   An array of attachments, for use with #attached.
   *
   * @see drupal_process_attached()
   */
  public function getAllEditorAttachments();
}
