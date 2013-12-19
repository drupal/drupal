<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\FormEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the form in-place editor.
 *
 * @InPlaceEditor(
 *   id = "form"
 * )
 */
class FormEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  function isCompatible(FieldDefinitionInterface $field_definition, array $items) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.inPlaceEditor.form'),
      ),
    );
  }

}
