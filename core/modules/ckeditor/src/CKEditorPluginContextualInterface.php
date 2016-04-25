<?php

namespace Drupal\ckeditor;

use Drupal\editor\Entity\Editor;

/**
 * Defines an interface for contextually enabled CKEditor plugins.
 *
 * Contextually enabled CKEditor plugins can be enabled via an explicit setting,
 * or enable themselves based on the configuration of another setting, such as
 * enabling based on a particular button being present in the toolbar.
 *
 * If a contextually enabled CKEditor plugin must also be configurable (for
 * instance, in the case where it must be enabled based on an explicit setting),
 * then one must also implement the CKEditorPluginConfigurableInterface
 * interface.
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginCssInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
 */
interface CKEditorPluginContextualInterface extends CKEditorPluginInterface {

  /**
   * Checks if this plugin should be enabled based on the editor configuration.
   *
   * The editor's settings can be retrieved via $editor->getSettings().
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return bool
   */
  public function isEnabled(Editor $editor);

}
