<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\CoreBundle;
use Drupal\Component\PhpStorage\PhpStorageInterface;
use Symfony\Component\HttpKernel\Kernel;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

/**
 * The DrupalKernel class is the core of Drupal itself.
 *
 * This class is responsible for building the Dependency Injection Container and
 * also deals with the registration of bundles. It allows registered bundles to
 * add their services to the container. Core provides the CoreBundle, which adds
 * the services required for all core subsystems. Each module can then add its
 * own bundle, i.e. a subclass of Symfony\Component\HttpKernel\Bundle, to
 * register services to the container.
 */
class DrupalKernel extends Kernel implements DrupalKernelInterface {

  /**
   * Holds the list of enabled modules.
   *
   * @var array
   */
  protected $moduleList;

  /**
   * Cache object for getting or setting the compiled container's class name.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $compilationIndexCache;

  /**
   * PHP code storage object to use for the compiled container.
   *
   * @var \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected $storage;

  /**
   * Constructs a DrupalKernel object.
   *
   * @param string $environment
   *   String indicating the environment, e.g. 'prod' or 'dev'. Used by
   *   Symfony\Component\HttpKernel\Kernel::__construct(). Drupal does not use
   *   this value currently. Pass 'prod'.
   * @param bool $debug
   *   Boolean indicating whether we are in debug mode. Used by
   *   Symfony\Component\HttpKernel\Kernel::__construct(). Drupal does not use
   *   this value currently. Pass TRUE.
   * @param array $module_list
   *   (optional) The array of enabled modules as returned by module_list().
   * @param Drupal\Core\Cache\CacheBackendInterface $compilation_index_cache
   *   (optional) If wanting to dump a compiled container to disk or use a
   *   previously compiled container, the cache object for the bin that stores
   *   the class name of the compiled container.
   */
  public function __construct($environment, $debug, array $module_list = NULL, CacheBackendInterface $compilation_index_cache = NULL) {
    parent::__construct($environment, $debug);
    $this->compilationIndexCache = $compilation_index_cache;
    $this->storage = drupal_php_storage('service_container');
    if (isset($module_list)) {
      $this->moduleList = $module_list;
    }
    else {
      // @todo This is a temporary measure which will no longer be necessary
      //   once we have an ExtensionHandler for managing this list. See
      //   http://drupal.org/node/1331486.
      $this->moduleList = module_list();
    }
  }

  /**
   * Overrides Kernel::init().
   */
  public function init() {
    // Intentionally empty. The sole purpose is to not execute Kernel::init(),
    // since that overrides/breaks Drupal's current error handling.
    // @todo Investigate whether it is possible to migrate Drupal's error
    //   handling to the one of Kernel without losing functionality.
  }

  /**
   * Returns an array of available bundles.
   */
  public function registerBundles() {
    $bundles = array(
      new CoreBundle(),
    );

    foreach ($this->moduleList as $module) {
      $camelized = ContainerBuilder::camelize($module);
      $class = "Drupal\\{$module}\\{$camelized}Bundle";
      if (class_exists($class)) {
        $bundles[] = new $class();
      }
    }
    return $bundles;
  }

  /**
   * Implements Drupal\Core\DrupalKernelInterface::updateModules().
   */
  public function updateModules($module_list) {
    $this->moduleList = $module_list;
    // If we haven't yet booted, we don't need to do anything: the new module
    // list will take effect when boot() is called. If we have already booted,
    // then reboot in order to refresh the bundle list and container.
    if ($this->booted) {
      drupal_container(NULL, TRUE);
      $this->booted = FALSE;
      $this->boot();
    }
  }

  /**
   * Initializes the service container.
   */
  protected function initializeContainer() {
    $this->container = NULL;
    if ($this->compilationIndexCache) {
      // The name of the compiled container class is generated from the hash of
      // its contents and cached. This enables multiple compiled containers
      // (for example, for different states of which modules are enabled) to
      // exist simultaneously on disk and in memory.
      if ($cache = $this->compilationIndexCache->get(implode(':', array('service_container', $this->environment, $this->debug)))) {
        $class = $cache->data;
        $cache_file = $class . '.php';

        // First, try to load.
        if (!class_exists($class, FALSE)) {
          $this->storage->load($cache_file);
        }
        // If the load succeeded or the class already existed, use it.
        if (class_exists($class, FALSE)) {
          $fully_qualified_class_name = '\\' . $class;
          $this->container = new $fully_qualified_class_name;
        }
      }
    }
    if (!isset($this->container)) {
      $this->container = $this->buildContainer();
      if ($this->compilationIndexCache && !$this->dumpDrupalContainer($this->container, $this->getContainerBaseClass())) {
        // We want to log this as an error but we cannot call watchdog() until
        // the container has been fully built and set in drupal_container().
        $error = 'Container cannot be written to disk';
      }
    }

    $this->container->set('kernel', $this);

    drupal_container($this->container);

    if (isset($error)) {
      watchdog('DrupalKernel', $error);
    }
  }

  /**
   * Builds the service container.
   *
   * @return ContainerBuilder The compiled service container
   */
  protected function buildContainer() {
    $container = $this->getContainerBuilder();

    // Merge in the minimal bootstrap container.
    if ($bootstrap_container = drupal_container()) {
      $container->merge($bootstrap_container);
    }
    foreach ($this->bundles as $bundle) {
      $bundle->build($container);
    }
    $container->compile();
    return $container;
  }

  /**
   * Gets a new ContainerBuilder instance used to build the service container.
   *
   * @return ContainerBuilder
   */
  protected function getContainerBuilder() {
    return new ContainerBuilder(new ParameterBag($this->getKernelParameters()));
  }

  /**
   * Dumps the service container to PHP code in the config directory.
   *
   * This method is based on the dumpContainer method in the parent class, but
   * that method is reliant on the Config component which we do not use here.
   *
   * @param ContainerBuilder $container
   *   The service container.
   * @param string $baseClass
   *   The name of the container's base class
   *
   * @return bool
   *   TRUE if the container was successfully dumped to disk.
   */
  protected function dumpDrupalContainer(ContainerBuilder $container, $baseClass) {
    if (!$this->storage->writeable()) {
      return FALSE;
    }
    // Cache the container.
    $dumper = new PhpDumper($container);
    $content = $dumper->dump(array('class' => 'DrupalServiceContainerStub', 'base_class' => $baseClass));
    $class = 'c' . hash('sha256', $content);
    $content = str_replace('DrupalServiceContainerStub', $class, $content);
    $this->compilationIndexCache->set(implode(':', array('service_container', $this->environment, $this->debug)), $class);

    return $this->storage->save($class . '.php', $content);
  }

  /**
   * Overrides and eliminates this method from the parent class. Do not use.
   *
   * This method is part of the KernelInterface interface, but takes an object
   * implementing LoaderInterface as its only parameter. This is part of the
   * Config compoment from Symfony, which is not provided by Drupal core.
   *
   * Modules wishing to provide an extension to this class which uses this
   * method are responsible for ensuring the Config component exists.
   */
  public function registerContainerConfiguration(LoaderInterface $loader) {
  }

}
