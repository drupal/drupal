<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\DefaultPluginManager.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Base class for plugin managers.
 *
 * @ingroup plugin_api
 */
class DefaultPluginManager extends PluginManagerBase implements PluginManagerInterface, CachedDiscoveryInterface {

  use DiscoveryCachedTrait;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * An array of cache tags to use for the cached definitions.
   *
   * @var array
   */
  protected $cacheTags = array();

  /**
   * Name of the alter hook if one should be invoked.
   *
   * @var string
   */
  protected $alterHook;

  /**
   * The subdirectory within a namespace to look for plugins, or FALSE if the
   * plugins are in the top level of the namespace.
   *
   * @var string|bool
   */
  protected $subdir;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A set of defaults to be referenced by $this->processDefinition() if
   * additional processing of plugins is necessary or helpful for development
   * purposes.
   *
   * @var array
   */
  protected $defaults = array();

  /**
   * Flag whether persistent caches should be used.
   *
   * @var bool
   */
  protected $useCaches = TRUE;

  /**
   * The name of the annotation that contains the plugin definition.
   *
   * @var string
   */
  protected $pluginDefinitionAnnotationName;

  /**
   * The interface each plugin should implement.
   *
   * @var string|null
   */
  protected $pluginInterface;

  /**
   * An object that implements \Traversable which contains the root paths
   * keyed by the corresponding namespace to look for plugin implementations.
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * Creates the discovery object.
   *
   * @param string|bool $subdir
   *   The plugin's subdirectory, for example Plugin/views/filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string|null $plugin_interface
   *   (optional) The interface each plugin should implement.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   */
  public function __construct($subdir, \Traversable $namespaces, ModuleHandlerInterface $module_handler, $plugin_interface = NULL, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin') {
    $this->subdir = $subdir;
    $this->namespaces = $namespaces;
    $this->pluginDefinitionAnnotationName = $plugin_definition_annotation_name;
    $this->pluginInterface = $plugin_interface;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Initialize the cache backend.
   *
   * Plugin definitions are cached using the provided cache backend. The
   * interface language is added as a suffix to the cache key.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param string $cache_key
   *   Cache key prefix to use, the language code will be appended
   *   automatically.
   * @param array $cache_tags
   *   (optional) When providing a list of cache tags, the cached plugin
   *   definitions are tagged with the provided cache tags. These cache tags can
   *   then be used to clear the corresponding cached plugin definitions. Note
   *   that this should be used with care! For clearing all cached plugin
   *   definitions of a plugin manager, call that plugin manager's
   *   clearCachedDefinitions() method. Only use cache tags when cached plugin
   *   definitions should be cleared along with other, related cache entries.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = array()) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($cache_tags)', 'Cache Tags must be strings.');
    $this->cacheBackend = $cache_backend;
    $this->cacheKey = $cache_key;
    $this->cacheTags = $cache_tags;
  }

  /**
   * Initializes the alter hook.
   *
   * @param string $alter_hook
   *   Name of the alter hook; for example, to invoke
   *   hook_mymodule_data_alter() pass in "mymodule_data".
   */
  protected function alterInfo($alter_hook) {
    $this->alterHook = $alter_hook;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    if ($this->cacheBackend) {
      if ($this->cacheTags) {
        // Use the cache tags to clear the cache.
        Cache::invalidateTags($this->cacheTags);
      }
      else {
        $this->cacheBackend->delete($this->cacheKey);
      }
    }
    $this->definitions = NULL;
  }

  /**
   * Returns the cached plugin definitions of the decorated discovery class.
   *
   * @return array|null
   *   On success this will return an array of plugin definitions. On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array
   *   and would actually be returned by the getDefinitions() method.
   */
  protected function getCachedDefinitions() {
    if (!isset($this->definitions) && $cache = $this->cacheGet($this->cacheKey)) {
      $this->definitions = $cache->data;
    }
    return $this->definitions;
  }

  /**
   * Sets a cache of plugin definitions for the decorated discovery class.
   *
   * @param array $definitions
   *   List of definitions to store in cache.
   */
  protected function setCachedDefinitions($definitions) {
    $this->cacheSet($this->cacheKey, $definitions, Cache::PERMANENT, $this->cacheTags);
    $this->definitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->useCaches = $use_caches;
    if (!$use_caches) {
      $this->definitions = NULL;
    }
  }

  /**
   * Fetches from the cache backend, respecting the use caches flag.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  protected function cacheGet($cid) {
    if ($this->useCaches && $this->cacheBackend) {
      return $this->cacheBackend->get($cid);
    }
    return FALSE;
  }

  /**
   * Stores data in the persistent cache, respecting the use caches flag.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   */
  protected function cacheSet($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    if ($this->cacheBackend && $this->useCaches) {
      $this->cacheBackend->set($cid, $data, $expire, $tags);
    }
  }


  /**
   * Performs extra processing on plugin definitions.
   *
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic they can do that by replacing or extending the
   * method.
   */
  public function processDefinition(&$definition, $plugin_id) {
    if (!empty($this->defaults) && is_array($this->defaults)) {
      $definition = NestedArray::mergeDeep($this->defaults, $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName);
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() {
    if (!$this->factory) {
      $this->factory = new ContainerFactory($this, $this->pluginInterface);
    }
    return $this->factory;
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      // If the plugin definition is an object, attempt to convert it to an
      // array, if that is not possible, skip further processing.
      if (is_object($plugin_definition) && !($plugin_definition = (array) $plugin_definition)) {
        continue;
      }
      if (isset($plugin_definition['provider']) && !in_array($plugin_definition['provider'], array('core', 'component')) && !$this->providerExists($plugin_definition['provider'])) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * Invokes the hook to alter the definitions if the alter hook is set.
   *
   * @param $definitions
   *   The discovered plugin defintions.
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }
  }

  /**
   * Determines if the provider of a definition exists.
   *
   * @return bool
   *   TRUE if provider exists, FALSE otherwise.
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider);
  }

}
