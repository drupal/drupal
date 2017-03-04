<?php

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
    return [
      'library' => [
        'quickedit/quickedit.inPlaceEditor.form',
      ],
    ];
  }

}
