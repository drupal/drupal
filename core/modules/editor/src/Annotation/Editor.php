<?php

/**
 * @file
 * Contains \Drupal\editor\Annotation\Editor.
 */

namespace Drupal\editor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Editor annotation object.
 *
 * Plugin Namespace: Plugin\Editor
 *
 * For a working example, see \Drupal\ckeditor\Plugin\Editor\CKEditor
 *
 * @see \Drupal\editor\Plugin\EditorPluginInterface
 * @see \Drupal\editor\Plugin\EditorBase
 * @see \Drupal\editor\Plugin\EditorManager
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
   * @var boolean
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

}
