<?php

/**
 * @file
 * Contains Drupal.
 */

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Static Service Container wrapper.
 *
 * Generally, code in Drupal should accept its dependencies via either
 * constructor injection or setter method injection. However, there are cases,
 * particularly in legacy procedural code, where that is infeasible. This
 * class acts as a unified global accessor to arbitrary services within the
 * system in order to ease the transition from procedural code to injected OO
 * code.
 *
 * The container is built by the kernel and passed in to this class which stores
 * it statically. The container always contains the services from
 * \Drupal\Core\CoreBundle, the bundles of enabled modules and any other bundles
 * defined in $GLOBALS['conf']['container_bundles'].
 *
 * This class exists only to support legacy code that cannot be dependency
 * injected. If your code needs it, consider refactoring it to be object
 * oriented, if possible. When this is not possible, for instance in the case of
 * hook implementations, and your code is more than a few non-reusable lines, it
 * is recommended to instantiate an object implementing the actual logic.
 *
 * @code
 *   // Legacy procedural code.
 *   function hook_do_stuff() {
 *     $lock = lock()->acquire('stuff_lock');
 *     // ...
 *   }
 *
 *   // Correct procedural code.
 *   function hook_do_stuff() {
 *     $lock = Drupal::lock()->acquire('stuff_lock');
 *     // ...
 *   }
 *
 *   // The preferred way: dependency injected code.
 *   function hook_do_stuff() {
 *     // Move the actual implementation to a class and instantiate it.
 *     $instance = new StuffDoingClass(Drupal::lock());
 *     $instance->doStuff();
 *
 *     // Or, even better, rely on the service container to avoid hard coding a
 *     // specific interface implementation, so that the actual logic can be
 *     // swapped. This might not always make sense, but in general it is a good
 *     // practice.
 *     Drupal::service('stuff.doing')->doStuff();
 *   }
 *
 *   interface StuffDoingInterface {
 *     public function doStuff();
 *   }
 *
 *   class StuffDoingClass implements StuffDoingInterface {
 *     protected $lockBackend;
 *
 *     public function __construct(LockBackendInterface $lockBackend) {
 *       $this->lockBackend = $lockBackend;
 *     }
 *
 *     public function doStuff() {
 *       $lock = $this->lockBackend->acquire('stuff_lock');
 *       // ...
 *     }
 *   }
 * @endcode
 *
 * @see \Drupal\Core\DrupalKernel
 */
class Drupal {

  /**
   * The currently active container object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected static $container;

  /**
   * Sets a new global container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A new container instance to replace the current.
   */
  public static function setContainer(ContainerInterface $container) {
    static::$container = $container;
  }

  /**
   * Returns the currently active global container.
   *
   * @deprecated This method is only useful for the testing environment, and as
   *   a BC shiv for drupal_container(). It should not be used otherwise.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public static function getContainer() {
    return static::$container;
  }

  /**
   * Retrieves a service from the container.
   *
   * Use this method if the desired service is not one of those with a dedicated
   * accessor method below. If it is listed below, those methods are preferred
   * as they can return useful type hints.
   *
   * @param string $id
   *   The ID of the service to retrieve.
   * @return mixed
   *   The specified service.
   */
  public static function service($id) {
    return static::$container->get($id);
  }

  /**
   * Returns the current primary database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The current active database's master connection.
   */
  public static function database() {
    return static::$container->get('database');
  }

  /**
   * Returns the requested cache bin.
   *
   * @param string $bin
   *   (optional) The cache bin for which the cache object should be returned,
   *   defaults to 'cache'.
   *
   * @return Drupal\Core\Cache\CacheBackendInterface
   *   The cache object associated with the specified bin.
   */
  public static function cache($bin = 'cache') {
    return static::$container->get('cache.' . $bin);
  }

  /**
   * Returns an expirable key value store collection.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   An expirable key value store collection.
   */
  public static function keyValueExpirable($collection) {
    return static::$container->get('keyvalue.expirable')->get($collection);
  }

  /**
   * Returns the locking layer instance.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   */
  public static function lock() {
    return static::$container->get('lock');
  }

}
