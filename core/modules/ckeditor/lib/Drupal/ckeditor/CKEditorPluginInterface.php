<?php

/**
 * @file
 * Contains \Drupal\ckeditor\CKEditorPluginInterface.
 */

namespace Drupal\ckeditor;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\editor\Plugin\Core\Entity\Editor;

/**
 * Defines an interface for (loading of) CKEditor plugins.
 *
 * This is the most basic CKEditor plugin interface; it provides the bare
 * minimum information. Solely implementing this interface is not sufficient to
 * be able to enable the plugin though — a CKEditor plugin can either be enabled
 * automatically when a button it provides is present in the toolbar, or when
 * some programmatically defined condition is true. In the former case,
 * implement the CKEditorPluginButtonsInterface interface, in the latter case,
 * implement the CKEditorPluginContextualInterface interface. It is also
 * possible to implement both, for advanced use cases.
 *
 * Finally, if your plugin must be configurable, you can also implement the
 * CKEditorPluginConfigurableInterface interface.
 *
 * @see CKEditorPluginButtonsInterface
 * @see CKEditorPluginContextualInterface
 * @see CKEditorPluginConfigurableInterface
 */
interface CKEditorPluginInterface extends PluginInspectionInterface {

  /**
   * Indicates if this plugin is part of the optimized CKEditor build.
   *
   * Plugins marked as internal are implicitly loaded as part of CKEditor.
   *
   * @return bool
   */
  public function isInternal();

  /**
   * Returns the Drupal root-relative file path to the plugin JavaScript file.
   *
   * Note: this does not use a Drupal library because this uses CKEditor's API,
   * see http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.resourceManager.html#addExternal.
   *
   * @return string|FALSE
   *   The Drupal root-relative path to the file, FALSE if an internal plugin.
   */
  public function getFile();

  /**
   * Returns the additions to CKEDITOR.config for a specific CKEditor instance.
   *
   * The editor's settings can be found in $editor->settings, but be aware that
   * it may not yet contain plugin-specific settings, because the user may not
   * yet have configured the form.
   * If there are plugin-specific settings (verify with isset()), they can be
   * found at $editor->settings['plugins'][$plugin_id].
   *
   * @param \Drupal\editor\Plugin\Core\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   A keyed array, whose keys will end up as keys under CKEDITOR.config.
   */
  public function getConfig(Editor $editor);
}
