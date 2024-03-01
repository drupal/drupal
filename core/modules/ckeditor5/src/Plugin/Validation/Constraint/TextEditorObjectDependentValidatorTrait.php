<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;

/**
 * Some CKEditor 5 constraint validators need a Text Editor object.
 */
trait TextEditorObjectDependentValidatorTrait {

  /**
   * Creates a text editor object from the execution context.
   *
   * Works both for an individual text editor config entity and a pair.
   *
   * @return \Drupal\editor\EditorInterface
   *   A text editor object, with the text format pre-populated.
   */
  private function createTextEditorObjectFromContext(): EditorInterface {
    if ($this->context->getRoot()->getDataDefinition()->getDataType() === 'ckeditor5_valid_pair__format_and_editor') {
      $text_format = FilterFormat::create([
        'filters' => $this->context->getRoot()->get('filters')->toArray(),
      ]);
    }
    else {
      assert(in_array($this->context->getRoot()->getDataDefinition()->getDataType(), ['editor.editor.*', 'entity:editor'], TRUE));
      $text_format = FilterFormat::load($this->context->getRoot()->get('format')->getValue());
      // This validator must not complain about a missing text format.
      // @see \Drupal\Tests\editor\Kernel\EditorValidationTest::testInvalidFormat()
      if ($text_format === NULL) {
        $text_format = FilterFormat::create([]);
      }
    }
    assert($text_format instanceof FilterFormatInterface);

    $text_editor = Editor::create([
      'editor' => 'ckeditor5',
      'settings' => $this->context->getRoot()->get('settings')->toArray(),
      'image_upload' => $this->context->getRoot()->get('image_upload')->toArray(),
      // Specify `filterFormat` to ensure that the generated Editor config
      // entity object already has the $filterFormat property set, to prevent
      // calls to Editor::hasAssociatedFilterFormat() and
      // Editor::getFilterFormat() from loading the FilterFormat from storage.
      // As far as this validation constraint validator is concerned, the
      // concrete FilterFormat entity ID does not matter, all that matters is
      // its filter configuration. Those exist in $text_format.
      'filterFormat' => $text_format,
    ]);
    assert($text_editor instanceof EditorInterface);

    return $text_editor;
  }

}
