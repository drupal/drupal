<?php

/**
 * @file
 * Contains \Drupal\ckeditor\CKEditorPluginContextualInterface.
 */

namespace Drupal\ckeditor;

use Drupal\editor\Entity\Editor;

/**
 * Defines an interface for contextually enabled CKEditor plugins.
 *
 * Contextually enabled CKEditor plugins can be enabled via an explicit setting,
 * or enable themselves based on the configuration of another setting, such as
 * enabling based on a particular button being present in the toolbar.
 *
 * If a contextually enabled CKEditor plugin must also be configurable (e.g. in
 * the case where it must be enabled based on an explicit setting), then one
 * must also implement the CKEditorPluginConfigurableInterface interface.
 *
 * @see CKEditorPluginConfigurableInterface
 */
interface CKEditorPluginContextualInterface extends CKEditorPluginInterface {

  /**
   * Checks if this plugin should be enabled based on the editor configuration.
   *
   * The editor's settings can be found in $editor->settings.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return bool
   */
  public function isEnabled(Editor $editor);

}
