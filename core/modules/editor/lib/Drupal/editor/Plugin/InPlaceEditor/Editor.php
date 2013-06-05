<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\InPlaceEditor\Editor.
 */

namespace Drupal\editor\Plugin\InPlaceEditor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\Core\Annotation\Translation;
use Drupal\edit\EditPluginInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines the formatted text editor.
 *
 * @InPlaceEditor(
 *   id = "editor",
 *   alternativeTo = {"direct"},
 *   module = "editor"
 * )
 */
class Editor extends PluginBase implements EditPluginInterface {

  /**
   * Implements \Drupal\edit\Plugin\EditPluginInterface::isCompatible().
   */
  function isCompatible(FieldInstance $instance, array $items) {
    $field = field_info_field($instance['field_name']);

    // This editor is incompatible with multivalued fields.
    if ($field['cardinality'] != 1) {
      return FALSE;
    }
    // This editor is compatible with processed ("rich") text fields; but only
    // if there is a currently active text format, that text format has an
    // associated editor and that editor supports inline editing.
    elseif (!empty($instance['settings']['text_processing'])) {
      $format_id = $items[0]['format'];
      if (isset($format_id) && $editor = editor_load($format_id)) {
        $definition = drupal_container()->get('plugin.manager.editor')->getDefinition($editor->editor);
        if ($definition['supports_inline_editing'] === TRUE) {
          return TRUE;
        }
      }

      return FALSE;
    }
  }

  /**
   * Implements \Drupal\edit\Plugin\EditPluginInterface::getMetadata().
   */
  function getMetadata(FieldInstance $instance, array $items) {
    $format_id = $items[0]['format'];
    $metadata['format'] = $format_id;
    $metadata['formatHasTransformations'] = $this->textFormatHasTransformationFilters($format_id);
    return $metadata;
  }

  /**
   * Returns whether the text format has transformation filters.
   */
  protected function textFormatHasTransformationFilters($format_id) {
    return (bool) count(array_intersect(array(FILTER_TYPE_TRANSFORM_REVERSIBLE, FILTER_TYPE_TRANSFORM_IRREVERSIBLE), filter_get_filter_types_by_format($format_id)));
  }

  /**
   * Implements \Drupal\edit\EditPluginInterface::getAttachments().
   */
  public function getAttachments() {
    global $user;

    $user_format_ids = array_keys(filter_formats($user));
    $manager = drupal_container()->get('plugin.manager.editor');
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
    $attachments['library'][] = array('editor', 'edit.formattedTextEditor.editor');

    return $attachments;
  }

}
