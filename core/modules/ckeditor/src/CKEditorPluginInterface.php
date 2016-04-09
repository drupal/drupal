<?php

namespace Drupal\ckeditor;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\editor\Entity\Editor;

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
 * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
 * @see \Drupal\ckeditor\CKEditorPluginContextualInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginCssInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
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
   * Returns a list of plugins this plugin requires.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   An unindexed array of plugin names this plugin requires. Each plugin is
   *   is identified by its annotated ID.
   */
  public function getDependencies(Editor $editor);

  /**
   * Returns a list of libraries this plugin requires.
   *
   * These libraries will be attached to the text_format element on which the
   * editor is being loaded.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   An array of libraries suitable for usage in a render API #attached
   *   property.
   */
  public function getLibraries(Editor $editor);

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
   * The editor's settings can be retrieved via $editor->getSettings(), but be
   * aware that it may not yet contain plugin-specific settings, because the
   * user may not yet have configured the form.
   * If there are plugin-specific settings (verify with isset()), they can be
   * found at
   * @code
   * $settings = $editor->getSettings();
   * $plugin_specific_settings = $settings['plugins'][$plugin_id];
   * @endcode
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   A keyed array, whose keys will end up as keys under CKEDITOR.config.
   */
  public function getConfig(Editor $editor);
}
