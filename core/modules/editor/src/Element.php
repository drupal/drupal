<?php

namespace Drupal\editor;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines a service for Text Editor's render elements.
 */
class Element {

  /**
   * The Text Editor plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new Element object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The Text Editor plugin manager service.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Additional #pre_render callback for 'text_format' elements.
   */
  function preRenderTextFormat(array $element) {
    // Allow modules to programmatically enforce no client-side editor by
    // setting the #editor property to FALSE.
    if (isset($element['#editor']) && !$element['#editor']) {
      return $element;
    }

    // filter_process_format() copies properties to the expanded 'value' child
    // element, including the #pre_render property. Skip this text format
    // widget, if it contains no 'format'.
    if (!isset($element['format'])) {
      return $element;
    }
    $format_ids = array_keys($element['format']['format']['#options']);

    // Early-return if no text editor is associated with any of the text formats.
    $editors = Editor::loadMultiple($format_ids);
    foreach ($editors as $key => $editor) {
      $definition = $this->pluginManager->getDefinition($editor->getEditor());
      if (!in_array($element['#base_type'], $definition['supported_element_types'])) {
        unset($editors[$key]);
      }
    }
    if (count($editors) === 0) {
      return $element;
    }

    // Use a hidden element for a single text format.
    $field_id = $element['value']['#id'];
    if (!$element['format']['format']['#access']) {
      // Use the first (and only) available text format.
      $format_id = $format_ids[0];
      $element['format']['editor'] = array(
        '#type' => 'hidden',
        '#name' => $element['format']['format']['#name'],
        '#value' => $format_id,
        '#attributes' => array(
          'data-editor-for' => $field_id,
        ),
      );
    }
    // Otherwise, attach to text format selector.
    else {
      $element['format']['format']['#attributes']['class'][] = 'editor';
      $element['format']['format']['#attributes']['data-editor-for'] = $field_id;
    }

    // Hide the text format's filters' guidelines of those text formats that have
    // a text editor associated: they're rather useless when using a text editor.
    foreach ($editors as $format_id => $editor) {
      $element['format']['guidelines'][$format_id]['#access'] = FALSE;
    }

    // Attach Text Editor module's (this module) library.
    $element['#attached']['library'][] = 'editor/drupal.editor';

    // Attach attachments for all available editors.
    $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $this->pluginManager->getAttachments($format_ids));

    // Apply XSS filters when editing content if necessary. Some types of text
    // editors cannot guarantee that the end user won't become a victim of XSS.
    if (!empty($element['value']['#value'])) {
      $original = $element['value']['#value'];
      $format = FilterFormat::load($element['format']['format']['#value']);

      // Ensure XSS-safety for the current text format/editor.
      $filtered = editor_filter_xss($original, $format);
      if ($filtered !== FALSE) {
        $element['value']['#value'] = $filtered;
      }

      // Only when the user has access to multiple text formats, we must add data-
      // attributes for the original value and change tracking, because they are
      // only necessary when the end user can switch between text formats/editors.
      if ($element['format']['format']['#access']) {
        $element['value']['#attributes']['data-editor-value-is-changed'] = 'false';
        $element['value']['#attributes']['data-editor-value-original'] = $original;
      }
    }

    return $element;
  }

}
