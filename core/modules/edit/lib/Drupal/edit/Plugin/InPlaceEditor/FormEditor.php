<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\FormEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the form editor.
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
