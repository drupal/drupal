<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkitManager.
 */

namespace Drupal\system\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages toolkit plugins.
 */
class ImageToolkitManager extends DefaultPluginManager {

  /**
   * Constructs the ImageToolkitManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    $annotation_namespaces = array('Drupal\system\Annotation' => $namespaces['Drupal\system']);
    parent::__construct('Plugin/ImageToolkit', $namespaces, $annotation_namespaces, 'Drupal\system\Annotation\ImageToolkit');
    $this->setCacheBackend($cache_backend, $language_manager, 'image_toolkit');
  }

  /**
   * Gets the default image toolkit.
   *
   * @param string $toolkit_id
   *   (optional) String specifying toolkit to load. NULL will load the default
   *   toolkit.
   *
   * @return \Drupal\system\Plugin\ImageToolkitInterface
   *   Object of the default toolkit, or FALSE on error.
   */
  public function getDefaultToolkit() {
    $toolkit_id = \Drupal::config('system.image')->get('toolkit');
    $toolkits = $this->getAvailableToolkits();

    if (!isset($toolkits[$toolkit_id]) || !class_exists($toolkits[$toolkit_id]['class'])) {
      // The selected toolkit isn't available so return the first one found. If
      // none are available this will return FALSE.
      reset($toolkits);
      $toolkit_id = key($toolkits);
    }

    if ($toolkit_id) {
      $toolkit = $this->createInstance($toolkit_id);
    }
    else {
      $toolkit = FALSE;
    }

    return $toolkit;
  }

  /**
   * Gets a list of available toolkits.
   *
   * @return array
   *   An array with the toolkit names as keys and the descriptions as values.
   */
  public function getAvailableToolkits() {
    // Use plugin system to get list of available toolkits.
    $toolkits = $this->getDefinitions();

    $output = array();
    foreach ($toolkits as $id => $definition) {
      // Only allow modules that aren't marked as unavailable.
      if (call_user_func($definition['class'] . '::isAvailable')) {
        $output[$id] = $definition;
      }
    }

    return $output;
  }
}
