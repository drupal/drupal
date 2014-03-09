<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\InPlaceEditor\Editor.
 */

namespace Drupal\editor\Plugin\InPlaceEditor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\edit\Plugin\InPlaceEditorInterface;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Defines the formatted text in-place editor.
 *
 * @InPlaceEditor(
 *   id = "editor",
 *   alternativeTo = {"plain_text"}
 * )
 */
class Editor extends PluginBase implements InPlaceEditorInterface {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();

    // This editor is incompatible with multivalued fields.
    if ($field_definition->getCardinality() != 1) {
      return FALSE;
    }
    // This editor is compatible with processed ("rich") text fields; but only
    // if there is a currently active text format, that text format has an
    // associated editor and that editor supports inline editing.
    elseif ($field_definition->getSetting('text_processing')) {
      if ($editor = editor_load($items[0]->format)) {
        $definition = \Drupal::service('plugin.manager.editor')->getDefinition($editor->editor);
        if ($definition['supports_inline_editing'] === TRUE) {
          return TRUE;
        }
      }

      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  function getMetadata(FieldItemListInterface $items) {
    $format_id = $items[0]->format;
    $metadata['format'] = $format_id;
    $metadata['formatHasTransformations'] = $this->textFormatHasTransformationFilters($format_id);
    return $metadata;
  }

  /**
   * Returns whether the text format has transformation filters.
   */
  protected function textFormatHasTransformationFilters($format_id) {
    $format = entity_load('filter_format', $format_id);
    return (bool) count(array_intersect(array(FilterInterface::TYPE_TRANSFORM_REVERSIBLE, FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE), $format->getFiltertypes()));
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    $user = \Drupal::currentUser();

    $user_format_ids = array_keys(filter_formats($user));
    $manager = \Drupal::service('plugin.manager.editor');
    $definitions = $manager->getDefinitions();

    // Filter the current user's formats to those that support inline editing.
    $formats = array();
    foreach ($user_format_ids as $format_id) {
      $editor = editor_load($format_id);
      if ($editor && isset($definitions[$editor->editor]) && isset($definitions[$editor->editor]['supports_inline_editing']) && $definitions[$editor->editor]['supports_inline_editing'] === TRUE) {
        $formats[] = $format_id;
      }
    }

    // Get the attachments for all text editors that the user might use.
    $attachments = $manager->getAttachments($formats);

    // Also include editor.module's formatted text editor.
    $attachments['library'][] = 'editor/edit.inPlaceEditor.formattedText';

    return $attachments;
  }

}
