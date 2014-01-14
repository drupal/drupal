<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelectorInterface.
 */

namespace Drupal\edit;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for selecting an in-place editor (an Editor plugin) for a field.
 */
interface EditorSelectorInterface {

  /**
   * Returns the in-place editor (an Editor plugin) to use for a field.
   *
   * @param string $formatter_type
   *   The field's formatter type name.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be in-place edited.
   *
   * @return string|null
   *   The editor to use, or NULL to not enable in-place editing.
   */
  public function getEditor($formatter_type, FieldItemListInterface $items);

  /**
   * Returns the attachments for all editors.
   *
   * @param array $editor_ids
   *   A list of all in-place editor IDs that should be attached.
   *
   * @return array
   *   An array of attachments, for use with #attached.
   *
   * @see drupal_process_attached()
   */
  public function getEditorAttachments(array $editor_ids);

}
