<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelector.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\field\FieldInstance;

/**
 * Selects an in-place editor (an Editor plugin) for a field.
 */
class EditorSelector implements EditorSelectorInterface {

  /**
   * The manager for editor (Create.js PropertyEditor widget) plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * A list of alternative editor plugin IDs, keyed by editor plugin ID.
   *
   * @var array
   */
  protected $alternatives;

  /**
   * Constructs a new EditorSelector.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The manager for Create.js PropertyEditor widget plugins.
   */
  public function __construct(PluginManagerInterface $editor_manager) {
    $this->editorManager = $editor_manager;
  }

  /**
   * Implements \Drupal\edit\EditorSelectorInterface::getEditor().
   */
  public function getEditor($formatter_type, FieldInstance $instance, array $items) {
    // Build a static cache of the editors that have registered themselves as
    // alternatives to a certain editor.
    if (!isset($this->alternatives)) {
      $editors = $this->editorManager->getDefinitions();
      foreach ($editors as $alternative_editor_id => $editor) {
        if (isset($editor['alternativeTo'])) {
          foreach ($editor['alternativeTo'] as $original_editor_id) {
            $this->alternatives[$original_editor_id][] = $alternative_editor_id;
          }
        }
      }
    }

    // Check if the formatter defines an appropriate in-place editor. For
    // example, text formatters displaying untrimmed text can choose to use the
    // 'direct' editor. If the formatter doesn't specify, fall back to the
    // 'form' editor, since that can work for any field. Formatter definitions
    // can use 'disabled' to explicitly opt out of in-place editing.
    $formatter_info = field_info_formatter_types($formatter_type);
    $editor_id = isset($formatter_info['edit']['editor']) ? $formatter_info['edit']['editor'] : 'form';
    if ($editor_id === 'disabled') {
      return;
    }
    elseif ($editor_id === 'form') {
      return 'form';
    }

    // No early return, so create a list of all choices.
    $editor_choices = array($editor_id);
    if (isset($this->alternatives[$editor_id])) {
      $editor_choices = array_merge($editor_choices, $this->alternatives[$editor_id]);
    }

    // Make a choice.
    foreach ($editor_choices as $editor_id) {
      $editor = $this->editorManager->createInstance($editor_id);
      if ($editor->isCompatible($instance, $items)) {
        return $editor_id;
      }
    }

    // We still don't have a choice, so fall back to the default 'form' editor.
    return 'form';
  }

  /**
   * Implements \Drupal\edit\EditorSelectorInterface::getAllEditorAttachments().
   *
   * @todo Instead of loading all JS/CSS for all editors, load them lazily when
   *   needed.
   * @todo The NestedArray stuff is wonky.
   */
  public function getAllEditorAttachments() {
    $attachments = array();
    $definitions = $this->editorManager->getDefinitions();

    // Editor plugins' attachments.
    $editor_ids = array_keys($definitions);
    foreach ($editor_ids as $editor_id) {
      $editor = $this->editorManager->createInstance($editor_id);
      $attachments[] = $editor->getAttachments();;
    }

    // JavaScript settings for Edit.
    foreach ($definitions as $definition) {
      $attachments[] = array(
        // This will be used in Create.js' propertyEditorWidgetsConfiguration.
        'js' => array(
          array(
            'type' => 'setting',
            'data' => array('edit' => array('editors' => array(
              $definition['id'] => array('widget' => $definition['jsClassName'])
            )))
          )
        )
      );
    }

    return NestedArray::mergeDeepArray($attachments);
  }
}
