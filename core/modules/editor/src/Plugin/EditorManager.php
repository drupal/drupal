<?php

namespace Drupal\editor\Plugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\editor\Attribute\Editor;

/**
 * Configurable text editor manager.
 *
 * @see \Drupal\editor\Annotation\Editor
 * @see \Drupal\editor\Plugin\EditorPluginInterface
 * @see \Drupal\editor\Plugin\EditorBase
 * @see plugin_api
 */
class EditorManager extends DefaultPluginManager {

  /**
   * Static cache of attachments.
   *
   * @var array
   */
  protected array $attachments = ['library' => []];

  /**
   * Editors.
   *
   * @var array
   */
  protected array $editors = [];

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, protected ?EntityTypeManagerInterface $entityTypeManager = NULL) {
    parent::__construct('Plugin/Editor', $namespaces, $module_handler, EditorPluginInterface::class, Editor::class, 'Drupal\editor\Annotation\Editor');
    $this->alterInfo('editor_info');
    $this->setCacheBackend($cache_backend, 'editor_plugins');
    if ($this->entityTypeManager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entityTypeManager argument is deprecated in drupal:11.2.0 and will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3447794', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
  }

  /**
   * Populates a key-value pair of available text editors.
   *
   * @return array
   *   An array of translated text editor labels, keyed by ID.
   */
  public function listOptions() {
    $options = [];
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
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   */
  public function getAttachments(array $format_ids) {
    $settings = $this->attachments['drupalSettings'] ?? [];

    if (($editor_ids_to_load = array_diff($format_ids, array_keys($this->editors)))) {
      $editors = $this->entityTypeManager->getStorage('editor')
        ->loadMultiple($editor_ids_to_load);
      // Statically cache the editors and include NULL entries for formats that
      // do not have editors.
      $this->editors += $editors + array_fill_keys($editor_ids_to_load, NULL);
      foreach ($editors as $format_id => $editor) {
        $plugin = $this->createInstance($editor->getEditor());
        $plugin_definition = $plugin->getPluginDefinition();

        // Libraries.
        $this->attachments['library'] = array_merge($this->attachments['library'], $plugin->getLibraries($editor));

        // Format-specific JavaScript settings.
        $settings['editor']['formats'][$format_id] = [
          'format' => $format_id,
          'editor' => $editor->getEditor(),
          'editorSettings' => $plugin->getJSSettings($editor),
          'editorSupportsContentFiltering' => $plugin_definition['supports_content_filtering'],
          'isXssSafe' => $plugin_definition['is_xss_safe'],
        ];
      }
    }

    // Allow other modules to alter all JavaScript settings.
    $this->moduleHandler->alter('editor_js_settings', $settings);

    if (empty($this->attachments['library']) && empty($settings)) {
      return [];
    }

    $this->attachments['drupalSettings'] = $settings;

    return $this->attachments;
  }

}
