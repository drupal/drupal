<?php

/**
 * @file
 * Contains \Drupal\ckeditor\CKEditorPluginManager.
 */

namespace Drupal\ckeditor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\editor\Entity\Editor;

/**
 * Provides a CKEditor Plugin plugin manager.
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
 * @see \Drupal\ckeditor\CKEditorPluginContextualInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
 */
class CKEditorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a CKEditorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/CKEditorPlugin', $namespaces, $module_handler, 'Drupal\ckeditor\Annotation\CKEditorPlugin');
    $this->alterInfo('ckeditor_plugin_info');
    $this->setCacheBackend($cache_backend, 'ckeditor_plugins');
  }

  /**
   * Retrieves enabled plugins' files, keyed by plugin ID.
   *
   * For CKEditor plugins that implement:
   *  - CKEditorPluginButtonsInterface, not CKEditorPluginContextualInterface,
   *     a plugin is enabled if at least one of its buttons is in the toolbar;
   *  - CKEditorPluginContextualInterface, not CKEditorPluginButtonsInterface,
   *     a plugin is enabled if its isEnabled() method returns TRUE
   *  - both of these interfaces, a plugin is enabled if either is the case.
   *
   * Internal plugins (those that are part of the bundled build of CKEditor) are
   * excluded by default, since they are loaded implicitly. If you need to know
   * even implicitly loaded (i.e. internal) plugins, then set the optional
   * second parameter.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @param bool $include_internal_plugins
   *   Defaults to FALSE. When set to TRUE, plugins whose isInternal() method
   *   returns TRUE will also be included.
   * @return array
   *   A list of the enabled CKEditor plugins, with the plugin IDs as keys and
   *   the Drupal root-relative plugin files as values.
   *   For internal plugins, the value is NULL.
   */
  public function getEnabledPluginFiles(Editor $editor, $include_internal_plugins = FALSE) {
    $plugins = array_keys($this->getDefinitions());
    // Flatten each row.
    $toolbar_rows = array();
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row_number => $row) {
      $toolbar_rows[] = array_reduce($settings['toolbar']['rows'][$row_number], function (&$result, $button_group) {
        return array_merge($result, $button_group['items']);
      }, array());
    }
    $toolbar_buttons = array_unique(NestedArray::mergeDeepArray($toolbar_rows));
    $enabled_plugins = array();
    $additional_plugins = array();

    foreach ($plugins as $plugin_id) {
      $plugin = $this->createInstance($plugin_id);

      if (!$include_internal_plugins && $plugin->isInternal()) {
        continue;
      }

      $enabled = FALSE;
      // Enable this plugin if it provides a button that has been enabled.
      if ($plugin instanceof CKEditorPluginButtonsInterface) {
        $plugin_buttons = array_keys($plugin->getButtons());
        $enabled = (count(array_intersect($toolbar_buttons, $plugin_buttons)) > 0);
      }
      // Otherwise enable this plugin if it declares itself as enabled.
      if (!$enabled && $plugin instanceof CKEditorPluginContextualInterface) {
        $enabled = $plugin->isEnabled($editor);
      }

      if ($enabled) {
        $enabled_plugins[$plugin_id] = ($plugin->isInternal()) ? NULL : $plugin->getFile();
        // Check if this plugin has dependencies that also need to be enabled.
        $additional_plugins = array_merge($additional_plugins, array_diff($plugin->getDependencies($editor), $additional_plugins));
      }
    }

    // Add the list of dependent plugins.
    foreach ($additional_plugins as $plugin_id) {
      $plugin = $this->createInstance($plugin_id);
      $enabled_plugins[$plugin_id] = ($plugin->isInternal()) ? NULL : $plugin->getFile();
    }

    // Always return plugins in the same order.
    asort($enabled_plugins);

    return $enabled_plugins;
  }

  /**
   * Retrieves all available CKEditor buttons, keyed by plugin ID.
   *
   * @return array
   *   All available CKEditor buttons, with plugin IDs as keys and button
   *   metadata (as implemented by getButtons()) as values.
   *
   * @see CKEditorPluginButtonsInterface::getButtons()
   */
  public function getButtons() {
    $plugins = array_keys($this->getDefinitions());
    $buttons_plugins = array();

    foreach ($plugins as $plugin_id) {
      $plugin = $this->createInstance($plugin_id);
      if ($plugin instanceof CKEditorPluginButtonsInterface) {
        $buttons_plugins[$plugin_id] = $plugin->getButtons();
      }
    }

    return $buttons_plugins;
  }

  /**
   * Injects the CKEditor plugins settings forms as a vertical tabs subform.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   */
  public function injectPluginSettingsForm(array &$form, FormStateInterface $form_state, Editor $editor) {
    $definitions = $this->getDefinitions();

    foreach (array_keys($definitions) as $plugin_id) {
      $plugin = $this->createInstance($plugin_id);
      if ($plugin instanceof CKEditorPluginConfigurableInterface) {
        $plugin_settings_form = array();
        $form['plugins'][$plugin_id] = array(
          '#type' => 'details',
          '#title' => $definitions[$plugin_id]['label'],
          '#open' => TRUE,
          '#group' => 'editor][settings][plugin_settings',
          '#attributes' => array(
            'data-ckeditor-plugin-id' => $plugin_id,
          ),
        );
        // Provide enough metadata for the drupal.ckeditor.admin library to
        // allow it to automatically show/hide the vertical tab containing the
        // settings for this plugin. Only do this if it's a CKEditor plugin that
        // just provides buttons, don't do this if it's a contextually enabled
        // CKEditor plugin. After all, in the latter case, we can't know when
        // its settings should be shown!
        if ($plugin instanceof CKEditorPluginButtonsInterface and !$plugin instanceof CKEditorPluginContextualInterface) {
          $form['plugins'][$plugin_id]['#attributes']['data-ckeditor-buttons'] = implode(' ', array_keys($plugin->getButtons()));
        }
        $form['plugins'][$plugin_id] += $plugin->settingsForm($plugin_settings_form, $form_state, $editor);
      }
    }
  }
}
