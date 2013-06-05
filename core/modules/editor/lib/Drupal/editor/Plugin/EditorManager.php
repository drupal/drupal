<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\InPlaceEditorManager.
 */

namespace Drupal\editor\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Configurable text editor manager.
 */
class EditorManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\editor\Annotation' => $namespaces['Drupal\editor']);
    $this->discovery = new AnnotatedClassDiscovery('Editor', $namespaces, $annotation_namespaces, 'Drupal\editor\Annotation\Editor');
    $this->discovery = new AlterDecorator($this->discovery, 'editor_info');
    $this->discovery = new CacheDecorator($this->discovery, 'editor');
    $this->factory = new DefaultFactory($this->discovery);
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
