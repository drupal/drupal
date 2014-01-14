<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelector.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterPluginManager;

/**
 * Selects an in-place editor (an Editor plugin) for a field.
 */
class EditorSelector implements EditorSelectorInterface {

  /**
   * The manager for editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The manager for formatter plugins.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager.
   */
  protected $formatterManager;

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
   *   The manager for editor plugins.
   * @param \Drupal\Core\Field\FormatterPluginManager
   *   The manager for formatter plugins.
   */
  public function __construct(PluginManagerInterface $editor_manager, FormatterPluginManager $formatter_manager) {
    $this->editorManager = $editor_manager;
    $this->formatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor($formatter_type, FieldItemListInterface $items) {
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
    // 'plain_text' editor. If the formatter doesn't specify, fall back to the
    // 'form' editor, since that can work for any field. Formatter definitions
    // can use 'disabled' to explicitly opt out of in-place editing.
    $formatter_info = $this->formatterManager->getDefinition($formatter_type);
    $editor_id = $formatter_info['edit']['editor'];
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
      if ($editor->isCompatible($items)) {
        return $editor_id;
      }
    }

    // We still don't have a choice, so fall back to the default 'form' editor.
    return 'form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorAttachments(array $editor_ids) {
    $attachments = array();
    $editor_ids = array_unique($editor_ids);

    // Editor plugins' attachments.
    foreach ($editor_ids as $editor_id) {
      $editor = $this->editorManager->createInstance($editor_id);
      $attachments[] = $editor->getAttachments();
    }

    return NestedArray::mergeDeepArray($attachments);
  }

}
