<?php

namespace Drupal\editor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Editor annotation object.
 *
 * Plugin Namespace: Plugin\Editor
 *
 * Text editor plugin implementations need to define a plugin definition array
 * through annotation. These definition arrays may be altered through
 * hook_editor_info_alter(). The definition includes the following keys:
 *
 * - id: The unique, system-wide identifier of the text editor. Typically named
 *   the same as the editor library.
 * - label: The human-readable name of the text editor, translated.
 * - supports_content_filtering: Whether the editor supports "allowed content
 *   only" filtering.
 * - supports_inline_editing: Whether the editor supports the inline editing
 *   provided by the Edit module.
 * - is_xss_safe: Whether this text editor is not vulnerable to XSS attacks.
 * - supported_element_types: On which form element #types this text editor is
 *   capable of working.
 *
 * A complete sample plugin definition should be defined as in this example:
 *
 * @code
 * @Editor(
 *   id = "my_editor",
 *   label = @Translation("My Editor"),
 *   supports_content_filtering = FALSE,
 *   supports_inline_editing = FALSE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea",
 *     "textfield",
 *   }
 * )
 * @endcode
 *
 * For a working example, see \Drupal\ckeditor\Plugin\Editor\CKEditor
 *
 * @see \Drupal\editor\Plugin\EditorPluginInterface
 * @see \Drupal\editor\Plugin\EditorBase
 * @see \Drupal\editor\Plugin\EditorManager
 * @see hook_editor_info_alter()
 * @see plugin_api
 *
 * @Annotation
 */
class Editor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the editor plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Whether the editor supports "allowed content only" filtering.
   *
   * @var bool
   */
  public $supports_content_filtering;

  /**
   * Whether the editor supports the inline editing provided by the Edit module.
   *
   * @var bool
   */
  public $supports_inline_editing;

  /**
   * Whether this text editor is not vulnerable to XSS attacks.
   *
   * @var bool
   */
  public $is_xss_safe;

  /**
   * A list of element types this text editor supports.
   *
   * @var string[]
   */
  public $supported_element_types;

}
