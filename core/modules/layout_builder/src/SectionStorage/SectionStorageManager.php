<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\layout_builder\Annotation\SectionStorage;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides the Section Storage type plugin manager.
 *
 * Note that while this class extends \Drupal\Core\Plugin\DefaultPluginManager
 * and includes many additional public methods, only some of them are available
 * via \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface.
 * While internally depending on the parent class is necessary, external code
 * should only use the methods available on that interface.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ContextHandlerInterface $context_handler = NULL) {
    parent::__construct('Plugin/SectionStorage', $namespaces, $module_handler, SectionStorageInterface::class, SectionStorage::class);

    if (!$context_handler) {
      @trigger_error('The context.handler service must be passed to \Drupal\layout_builder\SectionStorage\SectionStorageManager::__construct(); it was added in Drupal 8.7.0 and will be required before Drupal 9.0.0.', E_USER_DEPRECATED);
      $context_handler = \Drupal::service('context.handler');
    }
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
  public function loadEmpty($id) {
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromStorageId($type, $id) {
    @trigger_error('\Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::loadFromStorageId() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::load() should be used instead. See https://www.drupal.org/node/3012353.', E_USER_DEPRECATED);
    $contexts = $this->loadEmpty($type)->deriveContextsFromRoute($id, [], '', []);
    return $this->load($type, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromRoute($type, $value, $definition, $name, array $defaults) {
    @trigger_error('\Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::loadFromRoute() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute() and \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::load() should be used instead. See https://www.drupal.org/node/3012353.', E_USER_DEPRECATED);
    $contexts = $this->loadEmpty($type)->deriveContextsFromRoute($value, $definition, $name, $defaults);
    return $this->load($type, $contexts);
  }

}
