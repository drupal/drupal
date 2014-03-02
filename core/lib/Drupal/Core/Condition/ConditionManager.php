<?php

/**
 * @file
 * Contains \Drupal\Core\Condition\ConditionManager.
 */

namespace Drupal\Core\Condition;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for condition plugins.
 */
class ConditionManager extends DefaultPluginManager implements ExecutableManagerInterface {

  /**
   * Constructs a ConditionManager object.
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
    $this->alterInfo('condition_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'condition_plugins');

    parent::__construct('Plugin/Condition', $namespaces, $module_handler, 'Drupal\Core\Condition\Annotation\Condition');
  }

  /**
   * Override of Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin = $this->factory->createInstance($plugin_id, $configuration);
    return $plugin->setExecutableManager($this);
  }

  /**
   * Implements Drupal\Core\Executable\ExecutableManagerInterface::execute().
   */
  public function execute(ExecutableInterface $condition) {
    $result = $condition->evaluate();
    return $condition->isNegated() ? !$result : $result;
  }

}
