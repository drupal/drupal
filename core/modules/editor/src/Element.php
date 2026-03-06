<?php

namespace Drupal\editor;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\editor\EditorXssFilter\Standard;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Defines a service for Text Editor's render elements.
 */
class Element implements TrustedCallbackInterface {

  public function __construct(
    protected PluginManagerInterface $pluginManager,
    protected ?ModuleHandlerInterface $moduleHandler = NULL,
  ) {
    if (!$moduleHandler) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $moduleHandler argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3568146', E_USER_DEPRECATED);
      $this->moduleHandler = \Drupal::moduleHandler();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderTextFormat'];
  }

  /**
   * Additional #pre_render callback for 'text_format' elements.
   */
  public function preRenderTextFormat(array $element) {
    // Allow modules to programmatically enforce no client-side editor by
    // setting the #editor property to FALSE.
    if (isset($element['#editor']) && !$element['#editor']) {
      return $element;
    }

    // \Drupal\filter\Element\TextFormat::processFormat() copies properties to
    // the expanded 'value' to the child element, including the #pre_render
    // property. Skip this text format widget, if it contains no 'format'.
    if (!isset($element['format'])) {
      return $element;
    }
    $format_ids = array_keys($element['format']['format']['#options']);

    // Early-return if no text editor is associated with any of the text
    // formats.
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
      $element['format']['editor'] = [
        '#type' => 'hidden',
        '#name' => $element['format']['format']['#name'],
        '#value' => $format_id,
        '#attributes' => [
          'data-editor-for' => $field_id,
        ],
      ];
    }
    // Otherwise, attach to text format selector.
    else {
      $element['format']['format']['#attributes']['class'][] = 'editor';
      $element['format']['format']['#attributes']['data-editor-for'] = $field_id;
    }

    // Hide the text format's filters' guidelines of those text formats that
    // have a text editor associated: they're rather useless when using a text
    // editor.
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
      $filtered = $this->filterXss($original, $format);
      if ($filtered !== FALSE) {
        $element['value']['#value'] = $filtered;
      }

      // Only when the user has access to multiple text formats, we must add
      // data- attributes for the original value and change tracking, because
      // they are only necessary when the end user can switch between text
      // formats/editors.
      if ($element['format']['format']['#access']) {
        $element['value']['#attributes']['data-editor-value-is-changed'] = 'false';
        $element['value']['#attributes']['data-editor-value-original'] = $original;
      }
    }

    return $element;
  }

  /**
   * Applies text editor XSS filtering.
   *
   * @param string $html
   *   The HTML string that will be passed to the text editor.
   * @param \Drupal\filter\FilterFormatInterface|null $format
   *   The text format whose text editor will be used or NULL if the previously
   *   defined text format is now disabled.
   * @param \Drupal\filter\FilterFormatInterface|null $original_format
   *   (optional) The original text format (i.e. when switching text formats,
   *   $format is the text format that is going to be used, $original_format is
   *   the one that was being used initially, the one that is stored in the
   *   database when editing).
   *
   * @return string|false
   *   The XSS filtered string or FALSE when no XSS filtering needs to be
   *   applied, because one of the next conditions might occur:
   *   - No text editor is associated with the text format,
   *   - The previously defined text format is now disabled,
   *   - The text editor is safe from XSS,
   *   - The text format does not use any XSS protection filters.
   *
   * @see https://www.drupal.org/node/2099741
   */
  public function filterXss(string $html, ?FilterFormatInterface $format = NULL, ?FilterFormatInterface $original_format = NULL): string|false {
    $editor = $format ? Editor::load($format->id()) : NULL;

    // If no text editor is associated with this text format or the previously
    // defined text format is now disabled, then we don't need text editor XSS
    // filtering either.
    if (!isset($editor)) {
      return FALSE;
    }

    // If the text editor associated with this text format guarantees security,
    // then we also don't need text editor XSS filtering.
    $definition = $this->pluginManager->getDefinition($editor->getEditor());
    if ($definition['is_xss_safe'] === TRUE) {
      return FALSE;
    }

    // If there is no filter preventing XSS attacks in the text format being
    // used, then no text editor XSS filtering is needed either. (Because then
    // the editing user can already be attacked by merely viewing the content.)
    // E.g., an admin user creates content in Full HTML and then edits it, no
    // text format switching happens; in this case, no text editor XSS filtering
    // is desirable, because it would strip style attributes, amongst others.
    $current_filter_types = $format->getFilterTypes();
    if (!in_array(FilterInterface::TYPE_HTML_RESTRICTOR, $current_filter_types, TRUE)) {
      if ($original_format === NULL) {
        return FALSE;
      }
      // Unless we are switching from another text format, in which case we must
      // first check whether a filter preventing XSS attacks is used in that
      // text format, and if so, we must still apply XSS filtering. E.g., an
      // anonymous user creates content in Restricted HTML, an admin user edits
      // it (then no XSS filtering is applied because no text editor is used),
      // and switches to Full HTML (for which a text editor is used). Then we
      // must apply XSS filtering to protect the admin user.
      else {
        $original_filter_types = $original_format->getFilterTypes();
        if (!in_array(FilterInterface::TYPE_HTML_RESTRICTOR, $original_filter_types, TRUE)) {
          return FALSE;
        }
      }
    }

    // Otherwise, apply the text editor XSS filter. We use the default one
    // unless a module tells us to use a different one.
    $editor_xss_filter_class = Standard::class;
    $this->moduleHandler->alter('editor_xss_filter', $editor_xss_filter_class, $format, $original_format);

    return call_user_func($editor_xss_filter_class . '::filterXss', $html, $format, $original_format);
  }

}
