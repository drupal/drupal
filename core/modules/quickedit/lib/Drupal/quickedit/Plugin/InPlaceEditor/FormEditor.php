<?php

/**
 * @file
 * Contains \Drupal\quickedit\Plugin\InPlaceEditor\FormEditor.
 */

namespace Drupal\quickedit\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\quickedit\Plugin\InPlaceEditorBase;

/**
 * Defines the form in-place editor.
 *
 * @InPlaceEditor(
 *   id = "form"
 * )
 */
class FormEditor extends InPlaceEditorBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(FieldItemListInterface $items) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return array(
      'library' => array(
        'quickedit/quickedit.inPlaceEditor.form',
      ),
    );
  }

}
