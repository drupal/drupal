<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Drupal\Core\Config\FileStorage;
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
   *   An associative array whose keys are module names and whose values are
   *   ignored.
   */
  protected $moduleList;

  /**
   * Holds an updated list of enabled modules.
   *
   * @var array
   *   An associative array whose keys are module names and whose values are
   *   ignored.
   */
  protected $newModuleList;

  /**
   * An array of module data objects.
   *
   * The data objects have the same data structure as returned by
   * file_scan_directory() but only the uri property is used.
   *
   * @var array
   */
  protected $moduleData = array();

  /**
   * PHP code storage object to use for the compiled container.
   *
   * @var \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected $storage;

  /**
   * The classloader object.
   *
   * @var \Symfony\Component\ClassLoader\UniversalClassLoader
   */
  protected $classLoader;

  /**
   * The list of the classnames of the bundles in this kernel.
   *
   * @var array
   */
  protected $bundleClasses;

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
   * @param \Symfony\Component\ClassLoader\UniversalClassLoader $class_loader
   *   (optional) The classloader is only used if $storage is not given or
   *   the load from storage fails and a container rebuild is required. In
   *   this case, the loaded modules will be registered with this loader in
   *   order to be able to find the module bundles.
   * @param \Drupal\Component\PhpStorage\PhpStorageInterface $storage
   *   (optional) An object handling the load and save of the compiled
   *   container. If not specified, the container will neither be stored to
   *   disk nor read from there.
   */
  public function __construct($environment, $debug, UniversalClassLoader $class_loader, PhpStorageInterface $storage = NULL) {
    parent::__construct($environment, $debug);
    $this->storage = $storage;
    $this->classLoader = $class_loader;
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
   * Overrides Kernel::boot().
   */
  public function boot() {
    if ($this->booted) {
      return;
    }
    $this->initializeContainer();
    $this->booted = TRUE;
  }

  /**
   * Returns an array of available bundles.
   */
  public function registerBundles() {
    $bundles = array(
      new CoreBundle(),
    );
    if (!isset($this->moduleList)) {
      $storage = new FileStorage(config_get_config_directory());
      $module_list = $storage->read('system.module');
      $this->moduleList = isset($module_list['enabled']) ? $module_list['enabled'] : array();
    }

    $namespaces = $this->classLoader->getNamespaces();
    foreach ($this->moduleList as $module => $weight) {
      // When installing new modules, the modules in the list passed to
      // updateModules() do not yet have their namespace registered.
      $namespace = 'Drupal\\' . $module;
      if (!isset($namespaces[$namespace]) && $this->moduleData($module)) {
        $this->classLoader->registerNamespace($namespace, dirname(DRUPAL_ROOT . '/' . $this->moduleData($module)->uri) . '/lib');
      }
      $camelized = ContainerBuilder::camelize($module);
      $class = "Drupal\\{$module}\\{$camelized}Bundle";
      if (class_exists($class)) {
        $bundles[] = new $class();
        $this->bundleClasses[] = $class;
      }
    }
    return $bundles;
  }

  /**
   * Returns module data on the filesystem.
   *
   * @param $module
   *   The name of the module.
   *
   * @return \stdClass|bool
   *   Returns a stdClass object if the module data is found containing at
   *   least an uri property with the module path, for example
   *   core/modules/user/user.module.
   */
  protected function moduleData($module) {
    if (!$this->moduleData) {
      // Find filenames to prime the classloader. First, find profiles.
      // Profiles might want to add a bundle too and they also can contain
      // modules.
      $profiles_scanner = new SystemListing();
      $all_profiles = $profiles_scanner->scan('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.profile$/', 'profiles');
      $profiles = array_keys(array_intersect_key($this->moduleList, $all_profiles));
      $storage = new FileStorage(config_get_config_directory());
      // If a module is within a profile directory but specifies another
      // profile for testing, it needs to be found in the parent profile.
      if (($parent_profile_config = $storage->read('simpletest.settings')) && isset($parent_profile_config['parent_profile']) && $parent_profile_config['parent_profile'] != $profiles[0]) {
        // In case both profile directories contain the same extension, the
        // actual profile always has precedence.
        array_unshift($profiles, $parent_profile_config['parent_profile']);
      }
      // Now find modules.
      $modules_scanner = new SystemListing($profiles);
      $this->moduleData = $all_profiles + $modules_scanner->scan('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.module$/', 'modules');
    }
    return isset($this->moduleData[$module]) ? $this->moduleData[$module] : FALSE;
  }

  /**
   * Implements Drupal\Core\DrupalKernelInterface::updateModules().
   */
  public function updateModules(array $module_list, array $module_paths = array()) {
    $this->newModuleList = $module_list;
    foreach ($module_paths as $module => $path) {
      $this->moduleData[$module] = (object) array('uri' => $path);
    }
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
   * Returns the classname based on environment, debug and testing prefix.
   *
   * @return string
   *   The class name.
   */
  protected function getClassName() {
    $parts = array('service_container', $this->environment, $this->debug);
    // Make sure to use a testing-specific container even in the parent site.
    if (!empty($GLOBALS['drupal_test_info']['test_run_id'])) {
      $parts[] = $GLOBALS['drupal_test_info']['test_run_id'];
    }
    elseif ($prefix = drupal_valid_test_ua()) {
      $parts[] = $prefix;
    }
    return implode('_', $parts);
  }

  /**
   * Initializes the service container.
   */
  protected function initializeContainer() {
    $this->container = NULL;
    $class = $this->getClassName();
    $cache_file = $class . '.php';

    if ($this->storage) {
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
    // First check whether the list of modules changed in this request.
    if (isset($this->newModuleList)) {
      if (isset($this->container) && isset($this->moduleList) && array_keys($this->moduleList) !== array_keys($this->newModuleList)) {
        unset($this->container);
      }
      $this->moduleList = $this->newModuleList;
      unset($this->newModuleList);
    }
    // Second, verify that some other request -- for example on another
    // web frontend or during the installer -- changed the list of enabled
    // modules.
    if (isset($this->container)) {
      $module_list = $this->moduleList ?: $this->container->get('config.factory')->get('system.module')->load()->get('enabled');
      if (array_keys((array)$module_list) !== $this->container->getParameter('container.modules')) {
        unset($this->container);
      }
    }

    if (!isset($this->container)) {
      $this->container = $this->buildContainer();
      if ($this->storage && !$this->dumpDrupalContainer($this->container, $this->getContainerBaseClass())) {
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
    $this->initializeBundles();
    $container = $this->getContainerBuilder();
    $container->setParameter('container.bundles', $this->bundleClasses);
    $container->setParameter('container.modules', array_keys($this->moduleList));

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
    $class = $this->getClassName();
    $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass));
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
