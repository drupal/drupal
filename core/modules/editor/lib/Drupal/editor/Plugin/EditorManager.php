<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\EditorManager.
 */

namespace Drupal\editor\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Configurable text editor manager.
 */
class EditorManager extends DefaultPluginManager {

  /**
   * Constructs an EditorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Editor', $namespaces, $module_handler, 'Drupal\editor\Annotation\Editor');
    $this->alterInfo('editor_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'editor_plugins');
  }

  /**
   * Populates a key-value pair of available text editors.
   *
   * @return array
   *   An array of translated text editor labels, keyed by ID.
   */
  public function listOptions() {
    $options = array();
    foreach ($this->getDefinitions() as $key => $definition) {
      $options[$key] = $definition['label'];
    }
    return $options;
  }

  /**
   * Retrieves text editor libraries and JavaScript settings.
   *
   * @param array $format_ids
   *   An array of format IDs as returned by array_keys(filter_formats()).
   *
   * @return array
   *   An array of attachments, for use with #attached.
   *
   * @see drupal_process_attached()
   */
  public function getAttachments(array $format_ids) {
    $attachments = array('library' => array());

    $settings = array();
    foreach ($format_ids as $format_id) {
      $editor = editor_load($format_id);
      if (!$editor) {
        continue;
      }

      $plugin = $this->createInstance($editor->editor);
      $plugin_definition = $plugin->getPluginDefinition();

      // Libraries.
      $attachments['library'] = array_merge($attachments['library'], $plugin->getLibraries($editor));

      // Format-specific JavaScript settings.
      $settings['editor']['formats'][$format_id] = array(
        'format' => $format_id,
        'editor' => $editor->editor,
        'editorSettings' => $plugin->getJSSettings($editor),
        'editorSupportsContentFiltering' => $plugin_definition['supports_content_filtering'],
        'isXssSafe' => $plugin_definition['is_xss_safe'],
      );
    }

    // Allow other modules to alter all JavaScript settings.
    $this->moduleHandler->alter('editor_js_settings', $settings);

    if (empty($attachments['library']) && empty($settings)) {
      return array();
    }

    $attachments['js'][] = array(
      'type' => 'setting',
      'data' => $settings,
    );

    return $attachments;
  }

}
