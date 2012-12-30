<?php

/**
 * @file
 * Contains \Drupal\edit\EditorSelector.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\field\FieldInstance;

/**
 * Selects an in-place editor for a given entity field.
 */
class EditorSelector implements EditorSelectorInterface {

  /**
   * The manager for processed text editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $processedTextEditorManager;

  /**
   * The processed text editor plugin selected.
   *
   * @var \Drupal\edit\Plugin\ProcessedTextEditorInterface
   */
  protected $processedTextEditorPlugin;

  /**
   * Constructs a new EditorSelector.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $processed_text_editor_manager
   *   The manager for processed text editor plugins.
   */
  public function __construct(PluginManagerInterface $processed_text_editor_manager) {
    $this->processedTextEditorManager = $processed_text_editor_manager;
  }

  /**
   * Implements \Drupal\edit\EditorSelectorInterface::getEditor().
   */
  public function getEditor($formatter_type, FieldInstance $instance, array $items) {
    // Check if the formatter defines an appropriate in-place editor. For
    // example, text formatters displaying untrimmed text can choose to use the
    // 'direct' editor. If the formatter doesn't specify, fall back to the
    // 'form' editor, since that can work for any field. Formatter definitions
    // can use 'disabled' to explicitly opt out of in-place editing.
    $formatter_info = field_info_formatter_types($formatter_type);
    $editor = isset($formatter_info['edit']['editor']) ? $formatter_info['edit']['editor'] : 'form';
    if ($editor == 'disabled') {
      return;
    }

    // The same text formatters can be used for single-valued and multivalued
    // fields and for processed and unprocessed text, so we can't rely on the
    // formatter definition for the final determination, because:
    // - The direct editor does not work for multivalued fields.
    // - Processed text can benefit from a WYSIWYG editor.
    // - Empty processed text without an already selected format requires a form
    //   to select one.
    // @todo The processed text logic is too coupled to text fields. Figure out
    //   how to generalize to other textual field types.
    // @todo All of this might hint at formatter *definitions* not being the
    //   ideal place for editor specification. Moving the determination to
    //   something that works with instantiated formatters, not just their
    //   definitions, could alleviate that, but might come with its own
    //   challenges.
    if ($editor == 'direct') {
      $field = field_info_field($instance['field_name']);
      if ($field['cardinality'] != 1) {
        // The direct editor does not work for multivalued fields.
        $editor = 'form';
      }
      elseif (!empty($instance['settings']['text_processing'])) {
        $format_id = $items[0]['format'];
        if (isset($format_id)) {
          $wysiwyg_plugin = $this->getProcessedTextEditorPlugin();
          if (isset($wysiwyg_plugin) && $wysiwyg_plugin->checkFormatCompatibility($format_id)) {
            // Yay! Even though the text is processed, there's a WYSIWYG editor
            // that can work with it.
            $editor = 'direct-with-wysiwyg';
          }
          else {
            // @todo We might not have to downgrade all the way to 'form'. The
            //   'direct' editor might be appropriate for some kinds of
            //   processed text.
            $editor = 'form';
          }
        }
        else {
          // If a format is not yet selected, a form is needed to select one.
          $editor = 'form';
        }
      }
    }

    return $editor;
  }

  /**
   * Implements \Drupal\edit\EditorSelectorInterface::getAllEditorAttachments().
   */
  public function getAllEditorAttachments() {
    $this->getProcessedTextEditorPlugin();
    if (!isset($this->processedTextEditorPlugin)) {
      return array();
    }

    $js = array();

    // Add library and settings for the selected processed text editor plugin.
    $definition = $this->processedTextEditorPlugin->getDefinition();
    if (!empty($definition['library'])) {
      $js['library'][] = array($definition['library']['module'], $definition['library']['name']);
    }
    $this->processedTextEditorPlugin->addJsSettings();

    // Also add the setting to register it with Create.js
    if (!empty($definition['propertyEditorName'])) {
      $js['js'][] = array(
        'data' => array(
          'edit' => array(
            'wysiwygEditorWidgetName' => $definition['propertyEditorName'],
          ),
        ),
        'type' => 'setting'
      );
    }

    return $js;
  }

  /**
   * Returns the plugin to use for the 'direct-with-wysiwyg' editor.
   *
   * @return \Drupal\edit\Plugin\ProcessedTextEditorInterface
   *   The editor plugin.
   *
   * @todo We currently only support one plugin (the first one returned by the
   *   manager) for the 'direct-with-wysiwyg' editor on any given page. Enhance
   *   this to allow different ones per element (e.g., Aloha for one text field
   *   and CKEditor for another one).
   *
   * @todo The terminology here is confusing. 'direct-with-wysiwyg' is one of
   *   several possible "editor"s for processed text. When using it, we need to
   *   integrate a particular WYSIWYG editor, which in Create.js is called a
   *   "PropertyEditor widget", but we're not yet including "widget" in the name
   *   of ProcessedTextEditorInterface to minimize confusion with Field API
   *   widgets. So, we're currently refering to these as "plugins", which is
   *   correct in that it's using Drupal's Plugin API, but less informative than
   *   naming it "widget" or similar.
   */
  protected function getProcessedTextEditorPlugin() {
    if (!isset($this->processedTextEditorPlugin)) {
      $definitions = $this->processedTextEditorManager->getDefinitions();
      if (count($definitions)) {
        $plugin_ids = array_keys($definitions);
        $plugin_id = $plugin_ids[0];
        $this->processedTextEditorPlugin = $this->processedTextEditorManager->createInstance($plugin_id);
      }
    }
    return $this->processedTextEditorPlugin;
  }
}
