<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\CoreBundle;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\HttpKernel\Kernel;

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
   * Holds a list of enabled modules and their paths.
   *
   * This is used to store module data as a container parameter so that it can
   * be retrieved for registering namespaces when using a compiled container.
   * When not using a compiled container, the namespaces get registered during
   * the process of building the container.
   *
   * @var array
   *   An associative array whose keys are module names and whose values are
   *   module paths.
   */
  protected $modulePaths = array();

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
   * Config storage object used for reading enabled modules configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The list of the classnames of the bundles in this kernel.
   *
   * @var array
   */
  protected $bundleClasses;

  /**
   * Whether the container can be dumped.
   *
   * @var bool
   */
  protected $allowDumping;

  /**
   * Whether the container needs to be dumped once booting is complete.
   *
   * @var bool
   */
  protected $containerNeedsDumping;

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
   * @param bool $allow_dumping
   *   (optional) FALSE to stop the container from being written to or read
   *   from disk. Defaults to TRUE.
   */
  public function __construct($environment, $debug, UniversalClassLoader $class_loader, $allow_dumping = TRUE) {
    parent::__construct($environment, $debug);
    $this->classLoader = $class_loader;
    $this->allowDumping = $allow_dumping;
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
    // @todo Remove this once everything in the bootstrap has been converted to
    //   services in the DIC.
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);
    // Now that full bootstrap is complete, we can dump the container if it
    // was just rebuilt.
    if ($this->containerNeedsDumping && !$this->dumpDrupalContainer($this->container, $this->getContainerBaseClass())) {
      watchdog('DrupalKernel', 'Container cannot be written to disk');
    }
  }

  /**
   * Returns an array of available bundles.
   *
   * @return array
   *   The available bundles.
   */
  public function registerBundles() {
    $this->configStorage = BootstrapConfigStorageFactory::get();
    $bundles = array(
      new CoreBundle(),
    );
    if (!isset($this->moduleList)) {
      $module_list = $this->configStorage->read('system.module');
      $this->moduleList = isset($module_list['enabled']) ? $module_list['enabled'] : array();
    }

    $namespaces = $this->classLoader->getNamespaces();
    // We will need to store module locations in a container parameter so that
    // we can register all namespaces when using a compiled container.
    $this->modulePaths = array();
    foreach ($this->moduleList as $module => $weight) {
      // When installing new modules, the modules in the list passed to
      // updateModules() do not yet have their namespace registered.
      $namespace = 'Drupal\\' . $module;
      if (!isset($namespaces[$namespace]) && $this->moduleData($module)) {
        $path = dirname(DRUPAL_ROOT . '/' . $this->moduleData($module)->uri) . '/lib';
        $this->modulePaths[$module] = $path;
        $this->classLoader->registerNamespace($namespace, $path);
      }
      else {
        $this->modulePaths[$module] = $namespaces[$namespace];
      }
      $camelized = ContainerBuilder::camelize($module);
      $class = "Drupal\\{$module}\\{$camelized}Bundle";
      if (class_exists($class)) {
        $bundles[] = new $class();
        $this->bundleClasses[] = $class;
      }
    }
    // Add site specific or test bundles.
    if (!empty($GLOBALS['conf']['container_bundles'])) {
      foreach ($GLOBALS['conf']['container_bundles'] as $class) {
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
      // If a module is within a profile directory but specifies another
      // profile for testing, it needs to be found in the parent profile.
      if (($parent_profile_config = $this->configStorage->read('simpletest.settings')) && isset($parent_profile_config['parent_profile']) && $parent_profile_config['parent_profile'] != $profiles[0]) {
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

    if ($this->allowDumping) {
      // First, try to load.
      if (!class_exists($class, FALSE)) {
        $this->storage()->load($cache_file);
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
      // All namespaces must be registered before we attempt to use any service
      // from the container.
      $namespaces = $this->classLoader->getNamespaces();
      foreach ($this->container->getParameter('container.modules') as $module => $path) {
        $namespace = 'Drupal\\' . $module;
        if (!isset($namespaces[$namespace])) {
          $this->classLoader->registerNamespace($namespace, $path);
        }
      }
      $module_list = $this->moduleList ?: $this->container->get('config.factory')->get('system.module')->load()->get('enabled');
      if (array_keys((array)$module_list) !== array_keys($this->container->getParameter('container.modules'))) {
        unset($this->container);
      }
    }

    if (!isset($this->container)) {
      $this->container = $this->buildContainer();
      if ($this->allowDumping) {
        $this->containerNeedsDumping = TRUE;
      }
    }

    $this->container->set('kernel', $this);
    // Set the class loader which was registered as a synthetic service.
    $this->container->set('class_loader', $this->classLoader);

    drupal_container($this->container);
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
    $container->setParameter('container.modules', $this->modulePaths);
    // Register the class loader as a synthetic service.
    $container->register('class_loader', 'Symfony\Component\ClassLoader\UniversalClassLoader')->setSynthetic(TRUE);
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
    if (!$this->storage()->writeable()) {
      return FALSE;
    }
    // Cache the container.
    $dumper = new PhpDumper($container);
    $class = $this->getClassName();
    $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass));
    return $this->storage()->save($class . '.php', $content);
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

  /**
   * Gets the PHP code storage object to use for the compiled container.
   *
   * @return \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected function storage() {
    if (!isset($this->storage)) {
      $this->storage = PhpStorageFactory::get('service_container');
    }
    return $this->storage;
  }

}
