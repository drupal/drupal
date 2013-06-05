<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\FormEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines the form editor.
 *
 * @InPlaceEditor(
 *   id = "form",
 *   module = "edit"
 * )
 */
class FormEditor extends EditorBase {

  /**
   * Implements \Drupal\edit\EditPluginInterface::isCompatible().
   */
  function isCompatible(FieldInstance $instance, array $items) {
    return TRUE;
  }

  /**
   * Implements \Drupal\edit\EditPluginInterface::getAttachments().
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.editorWidget.form'),
      ),
    );
  }

}
