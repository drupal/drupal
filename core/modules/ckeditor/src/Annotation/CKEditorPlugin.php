<?php

namespace Drupal\ckeditor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CKEditorPlugin annotation object.
 *
 * Plugin Namespace: Plugin\CKEditorPlugin
 *
 * For a working example, see \Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalImage
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see hook_ckeditor_plugin_info_alter()
 * @see plugin_api
 *
 * @Annotation
 */
class CKEditorPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * This MUST match the name of the CKEditor plugin itself (written in
   * JavaScript). Otherwise CKEditor will throw JavaScript errors when it runs,
   * because it fails to load this CKEditor plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the CKEditor plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
