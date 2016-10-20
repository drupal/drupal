<?php

namespace Drupal\editor\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines an interface for configurable text editors.
 *
 * Modules implementing this interface may want to extend the EditorBase class,
 * which provides default implementations of each method where appropriate.
 *
 * If the editor's behavior depends on extensive options and/or external data,
 * then the implementing module can choose to provide a separate, global
 * configuration page rather than per-text-format settings. In that case, this
 * form should provide a link to the separate settings page.
 *
 * @see \Drupal\editor\Annotation\Editor
 * @see \Drupal\editor\Plugin\EditorBase
 * @see \Drupal\editor\Plugin\EditorManager
 * @see plugin_api
 */
interface EditorPluginInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Returns the default settings for this configurable text editor.
   *
   * @return array
   *   An array of settings as they would be stored by a configured text editor
   *   entity (\Drupal\editor\Entity\Editor).
   */
  public function getDefaultSettings();

  /**
   * Returns JavaScript settings to be attached.
   *
   * Most text editors use JavaScript to provide a WYSIWYG or toolbar on the
   * client-side interface. This method can be used to convert internal settings
   * of the text editor into JavaScript variables that will be accessible when
   * the text editor is loaded.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array of settings that will be added to the page for use by this text
   *   editor's JavaScript integration.
   *
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   * @see EditorManager::getAttachments()
   */
  public function getJSSettings(Editor $editor);

  /**
   * Returns libraries to be attached.
   *
   * Because this is a method, plugins can dynamically choose to attach a
   * different library for different configurations, instead of being forced to
   * always use the same method.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array of libraries that will be added to the page for use by this text
   *   editor.
   *
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   * @see EditorManager::getAttachments()
   */
  public function getLibraries(Editor $editor);

}
