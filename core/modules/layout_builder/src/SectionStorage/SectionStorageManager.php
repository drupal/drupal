<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\layout_builder\Annotation\SectionStorage;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides the Section Storage type plugin manager.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class SectionStorageManager extends DefaultPluginManager implements SectionStorageManagerInterface {

  /**
   * Constructs a new SectionStorageManager object.
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
    parent::__construct('Plugin/SectionStorage', $namespaces, $module_handler, SectionStorageInterface::class, SectionStorage::class);

    $this->alterInfo('layout_builder_section_storage');
    $this->setCacheBackend($cache_backend, 'layout_builder_section_storage_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function loadEmpty($id) {
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromStorageId($type, $id) {
    /** @var \Drupal\layout_builder\SectionStorageInterface $plugin */
    $plugin = $this->createInstance($type);
    return $plugin->setSectionList($plugin->getSectionListFromId($id));
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromRoute($type, $value, $definition, $name, array $defaults) {
    /** @var \Drupal\layout_builder\SectionStorageInterface $plugin */
    $plugin = $this->createInstance($type);
    if ($id = $plugin->extractIdFromRoute($value, $definition, $name, $defaults)) {
      try {
        return $plugin->setSectionList($plugin->getSectionListFromId($id));
      }
      catch (\InvalidArgumentException $e) {
        // Intentionally empty.
      }
    }
  }

}
