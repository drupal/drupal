<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelectorInterface.
 */

namespace Drupal\edit;

use Drupal\field\FieldInstance;

/**
 * Interface for selecting an in-place editor for a given entity field.
 */
interface EditorSelectorInterface {

  /**
   * Returns the in-place editor to use for a given field instance.
   *
   * The Edit module includes three in-place 'editors' that integrate with the
   * Create.js framework:
   * - direct: A minimal wrapper to simply setting the HTML5 contenteditable
   *   attribute on the DOM element.
   * - direct-with-wysiwyg: Binds a complete WYSIWYG editor (such as Aloha) to
   *   the DOM element.
   * - form: Fetches a simplified version of the field's edit form (widget) and
   *   overlays that over the DOM element.
   *
   * These three editors are registered in js/createjs/editable.js. Modules may
   * register additional editors via the Create.js API.
   *
   * This function returns the editor to use for a given field instance.
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
