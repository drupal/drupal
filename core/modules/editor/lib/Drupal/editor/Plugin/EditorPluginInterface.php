<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\EditorPluginInterface.
 */

namespace Drupal\editor\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines an interface for configurable text editors.
 *
 * Modules implementing this interface may want to extend the EditorBase class,
 * which provides default implementations of each method where appropriate.
 */
interface EditorPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the default settings for this configurable text editor.
   *
   * @return array
   *   An array of settings as they would be stored by a configured text editor
   *   entity (\Drupal\editor\Entity\Editor).
   */
  public function getDefaultSettings();

  /**
   * Returns a settings form to configure this text editor.
   *
   * If the editor's behavior depends on extensive options and/or external data,
   * then the implementing module can choose to provide a separate, global
   * configuration page rather than per-text-format settings. In that case, this
   * form should provide a link to the separate settings page.
   *
   * @param array $form
   *   An empty form array to be populated with a configuration form, if any.
   * @param array $form_state
   *   The state of the entire filter administration form.
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   A render array for the settings form.
   */
  public function settingsForm(array $form, array &$form_state, Editor $editor);

  /**
   * Validates the settings form for an editor.
   *
   * The contents of the editor settings are located in
   * $form_state['values']['editor']['settings']. Calls to form_error() should
   * reflect this location in the settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function settingsFormValidate(array $form, array &$form_state);

  /**
   * Modifies any values in the form state to prepare them for saving.
   *
   * Values in $form_state['values']['editor']['settings'] are saved by Editor
   * module in editor_form_filter_admin_format_submit().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function settingsFormSubmit(array $form, array &$form_state);

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
   * @see drupal_process_attached()
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
   * @see drupal_process_attached()
   * @see EditorManager::getAttachments()
   */
  public function getLibraries(Editor $editor);

}
