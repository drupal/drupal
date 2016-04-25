<?php

namespace Drupal\ckeditor;

use Drupal\editor\Entity\Editor;

/**
 * Defines an interface for CKEditor plugins with associated CSS.
 *
 * This allows a CKEditor plugin to add additional CSS in iframe CKEditor
 * instances without needing to implement hook_ckeditor_css_alter().
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
 * @see \Drupal\ckeditor\CKEditorPluginContextualInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
 */
interface CKEditorPluginCssInterface extends CKEditorPluginInterface {

  /**
   * Retrieves enabled plugins' iframe instance CSS files.
   *
   * Note: this does not use a Drupal asset library because this CSS will be
   * loaded by CKEditor, not by Drupal.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return string[]
   *   An array of CSS files. This is a flat list of file paths relative to
   *   the Drupal root.
   */
  public function getCssFiles(Editor $editor);

}
