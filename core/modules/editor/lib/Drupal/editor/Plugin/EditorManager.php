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
    $annotation_namespaces = array('Drupal\editor\Annotation' => $namespaces['Drupal\editor']);
    parent::__construct('Plugin/Editor', $namespaces, $annotation_namespaces, 'Drupal\editor\Annotation\Editor');
    $this->alterInfo($module_handler, 'editor_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'editor');
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

      // Libraries.
      $attachments['library'] = array_merge($attachments['library'], $plugin->getLibraries($editor));

      // JavaScript settings.
      $settings[$format_id] = array(
        'format' => $format_id,
        'editor' => $editor->editor,
        'editorSettings' => $plugin->getJSSettings($editor),
      );
    }

    // We have all JavaScript settings, allow other modules to alter them.
    drupal_alter('editor_js_settings', $settings);

    if (empty($attachments['library']) && empty($settings)) {
      return array();
    }

    $attachments['js'][] = array(
      'type' => 'setting',
      'data' => array('editor' => array('formats' => $settings)),
    );

    return $attachments;
  }

}
