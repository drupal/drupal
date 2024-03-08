<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\layout_builder\Attribute\SectionStorage;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides the Section Storage type plugin manager.
 *
 * Note that while this class extends \Drupal\Core\Plugin\DefaultPluginManager
 * and includes many additional public methods, only some of them are available
 * via \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface.
 * While internally depending on the parent class is necessary, external code
 * should only use the methods available on that interface.
 */
class SectionStorageManager extends DefaultPluginManager implements SectionStorageManagerInterface {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

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
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ContextHandlerInterface $context_handler) {
    parent::__construct('Plugin/SectionStorage', $namespaces, $module_handler, SectionStorageInterface::class, SectionStorage::class, '\Drupal\layout_builder\Annotation\SectionStorage');

    $this->contextHandler = $context_handler;

    $this->alterInfo('layout_builder_section_storage');
    $this->setCacheBackend($cache_backend, 'layout_builder_section_storage_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();

    // Sort the definitions by their weight while preserving the original order
    // for those with matching weights.
    $weights = array_map(function (SectionStorageDefinition $definition) {
      return $definition->getWeight();
    }, $definitions);
    $ids = array_keys($definitions);
    array_multisort($weights, $ids, $definitions);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function load($type, array $contexts = []) {
    $plugin = $this->loadEmpty($type);
    try {
      $this->contextHandler->applyContextMapping($plugin, $contexts);
    }
    catch (ContextException $e) {
      return NULL;
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function findByContext(array $contexts, RefinableCacheableDependencyInterface $cacheability) {
    $storage_types = array_keys($this->contextHandler->filterPluginDefinitionsByContexts($contexts, $this->getDefinitions()));

    // Add the manager as a cacheable dependency in order to vary by changes to
    // the plugin definitions.
    $cacheability->addCacheableDependency($this);

    foreach ($storage_types as $type) {
      $plugin = $this->load($type, $contexts);
      if ($plugin && $plugin->isApplicable($cacheability)) {
        return $plugin;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEmpty($type) {
    return $this->createInstance($type);
  }

}
