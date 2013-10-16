<?php

/**
 * @file
 * Contains \Drupal\search\SearchPluginManager.
 */

namespace Drupal\search;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * SearchExecute plugin manager.
 */
class SearchPluginManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, ConfigFactory $config_factory, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/Search', $namespaces, 'Drupal\search\Annotation\SearchPlugin');

    $this->configFactory = $config_factory;
    $this->setCacheBackend($cache_backend, $language_manager, 'search_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Fill in the provider as default values for missing keys.
    $definition += array(
      'title' => $definition['provider'],
      'path' => $definition['provider'],
    );
  }

  /**
   * Returns an instance for each active search plugin.
   *
   * @return \Drupal\search\Plugin\SearchInterface[]
   *   An array of active search plugins, keyed by their ID.
   */
  public function getActivePlugins() {
    $plugins = array();
    foreach ($this->getActiveDefinitions() as $plugin_id => $definition) {
      $plugins[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $plugins;
  }

  /**
   * Returns an instance for each active plugin that implements \Drupal\search\Plugin\SearchIndexingInterface.
   *
   * @return \Drupal\search\Plugin\SearchInterface[]
   *   An array of active search plugins, keyed by their ID.
   */
  public function getActiveIndexingPlugins() {
    $plugins = array();
    foreach ($this->getActiveDefinitions() as $plugin_id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\search\Plugin\SearchIndexingInterface')) {
        $plugins[$plugin_id] = $this->createInstance($plugin_id);
      }
    }
    return $plugins;
  }

  /**
   * Returns definitions for active search plugins keyed by their ID.
   *
   * @return array
   *   An array of active search plugin definitions, keyed by their ID.
   */
  public function getActiveDefinitions() {
    $active_definitions = array();
    $active_config = $this->configFactory->get('search.settings')->get('active_plugins');
    $active_plugins = $active_config ? array_flip($active_config) : array();
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (isset($active_plugins[$plugin_id])) {
        $active_definitions[$plugin_id] = $definition;
      }
    }
    return $active_definitions;
  }

  /**
   * Check whether access is allowed to search results from a given plugin.
   *
   * @param string $plugin_id
   *   The id of the plugin being checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked for access
   *
   * @return bool
   *   TRUE if access is allowed, FALSE otherwise.
   */
  public function pluginAccess($plugin_id, AccountInterface $account) {
    $definition = $this->getDefinition($plugin_id);
    if (empty($definition['class'])) {
      return FALSE;
    }
    // Plugins that implement AccessibleInterface can deny access.
    if (is_subclass_of($definition['class'], '\Drupal\Core\Access\AccessibleInterface')) {
      return $this->createInstance($plugin_id)->access('view', $account);
    }
    return TRUE;
  }
}
