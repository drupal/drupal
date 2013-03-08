<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\edit\editor\FormEditor.
 */

namespace Drupal\edit\Plugin\edit\editor;

use Drupal\edit\EditorBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\field\FieldInstance;

/**
 * Defines the "form" Create.js PropertyEditor widget.
 *
 * @Plugin(
 *   id = "form",
 *   jsClassName = "drupalFormWidget",
 *   module = "edit"
 * )
 */
class FormEditor extends EditorBase {

  /**
   * Implements \Drupal\edit\EditorInterface::isCompatible().
   */
  function isCompatible(FieldInstance $instance, array $items) {
    return TRUE;
  }

  /**
   * Implements \Drupal\edit\EditorInterface::getAttachments().
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.editor.form'),
      ),
    );
  }

}
