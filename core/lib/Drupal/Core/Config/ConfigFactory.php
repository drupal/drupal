<?php

/**
 * @file
 * Definition of Drupal\Core\Config\ConfigFactory.
 */

namespace Drupal\Core\Config;

/**
 * Defines the configuration object factory.
 *
 * The configuration object factory instantiates a Config object for each
 * configuration object name that is accessed and returns it to callers.
 *
 * @see Drupal\Core\Config\Config
 *
 * Each configuration object gets a storage dispatcher object injected, which
 * determines the storage controller to use for reading and writing the
 * configuration data.
 *
 * @see Drupal\Core\Config\StorageDispatcher
 */
class ConfigFactory {
  /**
   * A storage dispatcher instance to use for reading and writing configuration data.
   *
   * @var Drupal\Core\Config\StorageDispatcher
   */
  protected $storageDispatcher;

  /**
   * Constructs the Config factory.
   *
   * @param Drupal\Core\Config\StorageDispatcher $storage_dispatcher
   *   The storage dispatcher object to use for reading and writing
   *   configuration data.
   */
  public function __construct(StorageDispatcher $storage_dispatcher) {
    $this->storageDispatcher = $storage_dispatcher;
  }

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return Drupal\Core\Config\Config
   *   A configuration object with the given $name.
   */
  public function get($name) {
    // @todo Caching the instantiated objects per name might cut off a fair
    //   amount of CPU time and memory. Only the data within the configuration
    //   object changes, so the additional cost of instantiating duplicate
    //   objects could possibly be avoided. It is not uncommon for a
    //   configuration object to be retrieved many times during a single
    //   request; e.g., 'system.performance' alone is retrieved around 10-20
    //   times within a single page request. Sub-requests via HttpKernel will
    //   most likely only increase these counts.
    // @todo Benchmarks were performed with a script that essentially retained
    //   all instantiated configuration objects in memory until script execution
    //   ended. A variant of that script called config() within a helper
    //   function only, which inherently meant that PHP destroyed all
    //   configuration objects after leaving the function. Consequently,
    //   benchmark results looked entirely different. Profiling should probably
    //   redone under more realistic conditions; e.g., actual HTTP requests.
    // @todo The decrease of CPU time is interesting, since that means that
    //   ContainerBuilder involves plenty of function calls (which are known to
    //   be slow in PHP).
    $config = new Config($this->storageDispatcher);
    return $config->setName($name);
  }
}
