<?php

/**
 * @file
 * Documentation for Text Editor API.
 */

use Drupal\filter\FilterFormatInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Performs alterations on text editor definitions.
 *
 * @param array $editors
 *   An array of metadata of text editors, as collected by the plugin annotation
 *   discovery mechanism.
 *
 * @see \Drupal\editor\Plugin\EditorBase
 */
function hook_editor_info_alter(array &$editors) {
  $editors['some_other_editor']['label'] = t('A different name');
  $editors['some_other_editor']['library']['module'] = 'myeditoroverride';
}

/**
 * Modifies JavaScript settings that are added for text editors.
 *
 * @param array $settings
 *   All the settings that will be added to the page for the text formats to
 *   which a user has access.
 */
function hook_editor_js_settings_alter(array &$settings) {
  if (isset($settings['editor']['formats']['basic_html'])) {
    $settings['editor']['formats']['basic_html']['editor'] = 'MyDifferentEditor';
    $settings['editor']['formats']['basic_html']['editorSettings']['buttons'] = ['strong', 'italic', 'underline'];
  }
}

/**
 * Modifies the text editor XSS filter that will used for the given text format.
 *
 * Is only called when an EditorXssFilter will effectively be used; this hook
 * does not allow one to alter that decision.
 *
 * @param string &$editor_xss_filter_class
 *   The text editor XSS filter class that will be used.
 * @param \Drupal\filter\FilterFormatInterface $format
 *   The text format configuration entity. Provides context based upon which
 *   one may want to adjust the filtering.
 * @param \Drupal\filter\FilterFormatInterface|null $original_format
 *   (optional) The original text format configuration entity (when switching
 *   text formats/editors). Also provides context based upon which one may want
 *   to adjust the filtering.
 *
 * @see \Drupal\editor\EditorXssFilterInterface
 */
function hook_editor_xss_filter_alter(&$editor_xss_filter_class, FilterFormatInterface $format, FilterFormatInterface $original_format = NULL) {
  $filters = $format->filters()->getAll();
  if (isset($filters['filter_wysiwyg']) && $filters['filter_wysiwyg']->status) {
    $editor_xss_filter_class = '\Drupal\filter_wysiwyg\EditorXssFilter\WysiwygFilter';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
