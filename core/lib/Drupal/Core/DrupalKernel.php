<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\CoreServiceProvider;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\YamlFileLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Composer\Autoload\ClassLoader;

/**
 * The DrupalKernel class is the core of Drupal itself.
 *
 * This class is responsible for building the Dependency Injection Container and
 * also deals with the registration of service providers. It allows registered
 * service providers to add their services to the container. Core provides the
 * CoreServiceProvider, which, in addition to registering any core services that
 * cannot be registered in the core.services.yaml file, adds any compiler passes
 * needed by core, e.g. for processing tagged services. Each module can add its
 * own service provider, i.e. a class implementing
 * Drupal\Core\DependencyInjection\ServiceProvider, to register services to the
 * container, or modify existing services.
 */
class DrupalKernel implements DrupalKernelInterface, TerminableInterface {

  const CONTAINER_BASE_CLASS = '\Drupal\Core\DependencyInjection\Container';

  /**
   * Holds the container instance.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The environment, e.g. 'testing', 'install'.
   *
   * @var string
   */
  protected $environment;

  /**
   * Whether the kernel has been booted.
   *
   * @var bool
   */
  protected $booted;

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
   * @var \Composer\Autoload\ClassLoader
   */
  protected $classLoader;

  /**
   * Config storage object used for reading enabled modules configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The list of the classnames of the service providers in this kernel.
   *
   * @var array
   */
  protected $serviceProviderClasses;

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
   * Holds the list of YAML files containing service definitions.
   *
   * @var array
   */
  protected $serviceYamls;

  /**
   * The array of registered service providers.
   *
   * @var array
   */
  protected $serviceProviders;

  /**
   * Constructs a DrupalKernel object.
   *
   * @param string $environment
   *   String indicating the environment, e.g. 'prod' or 'dev'. Used by
   *   Symfony\Component\HttpKernel\Kernel::__construct(). Drupal does not use
   *   this value currently. Pass 'prod'.
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   (optional) The classloader is only used if $storage is not given or
   *   the load from storage fails and a container rebuild is required. In
   *   this case, the loaded modules will be registered with this loader in
   *   order to be able to find the module serviceProviders.
   * @param bool $allow_dumping
   *   (optional) FALSE to stop the container from being written to or read
   *   from disk. Defaults to TRUE.
   */
  public function __construct($environment, ClassLoader $class_loader, $allow_dumping = TRUE) {
    $this->environment = $environment;
    $this->booted = false;
    $this->classLoader = $class_loader;
    $this->allowDumping = $allow_dumping;
  }

  /**
   * {@inheritdoc}
   */
  public function boot() {
    if ($this->booted) {
      return;
    }
    $this->initializeContainer();
    $this->booted = TRUE;
    if ($this->containerNeedsDumping && !$this->dumpDrupalContainer($this->container, static::CONTAINER_BASE_CLASS)) {
      watchdog('DrupalKernel', 'Container cannot be written to disk');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function shutdown() {
    if (FALSE === $this->booted) {
      return;
    }
    $this->booted = FALSE;
    $this->container = null;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * {@inheritdoc}
   */
  public function discoverServiceProviders() {
    $this->configStorage = BootstrapConfigStorageFactory::get();
    $serviceProviders = array(
      'CoreServiceProvider' => new CoreServiceProvider(),
    );
    $this->serviceYamls = array(
      'core/core.services.yml'
    );
    $this->serviceProviderClasses = array('Drupal\Core\CoreServiceProvider');

    // Ensure we know what modules are enabled and that their namespaces are
    // registered.
    if (!isset($this->moduleList)) {
      $module_list = $this->configStorage->read('system.module');
      $this->moduleList = isset($module_list['enabled']) ? $module_list['enabled'] : array();
    }
    $module_filenames = $this->getModuleFileNames();
    $this->registerNamespaces($this->getModuleNamespaces($module_filenames));

    // Load each module's serviceProvider class.
    foreach ($this->moduleList as $module => $weight) {
      $camelized = ContainerBuilder::camelize($module);
      $name = "{$camelized}ServiceProvider";
      $class = "Drupal\\{$module}\\{$name}";
      if (class_exists($class)) {
        $serviceProviders[$name] = new $class();
        $this->serviceProviderClasses[] = $class;
      }
      $filename = dirname($module_filenames[$module]) . "/$module.services.yml";
      if (file_exists($filename)) {
        $this->serviceYamls[] = $filename;
      }
    }

    // Add site specific or test service providers.
    if (!empty($GLOBALS['conf']['container_service_providers'])) {
      foreach ($GLOBALS['conf']['container_service_providers'] as $name => $class) {
        $serviceProviders[$name] = new $class();
        $this->serviceProviderClasses[] = $class;
      }
    }
    // Add site specific or test YAMLs.
    if (!empty($GLOBALS['conf']['container_yamls'])) {
      $this->serviceYamls = array_merge($this->serviceYamls, $GLOBALS['conf']['container_yamls']);
    }
    return $serviceProviders;
  }


  /**
   * {@inheritdoc}
   */
  public function getServiceProviders() {
    return $this->serviceProviders;
  }

  /**
   * {@inheritdoc}
   */
  public function terminate(Request $request, Response $response) {
    if (FALSE === $this->booted) {
      return;
    }

    if ($this->getHttpKernel() instanceof TerminableInterface) {
      $this->getHttpKernel()->terminate($request, $response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
    if (FALSE === $this->booted) {
      $this->boot();
    }

    return $this->getHttpKernel()->handle($request, $type, $catch);
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
      // First, find profiles.
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
   *
   * @todo Remove obsolete $module_list parameter. Only $module_filenames is
   *   needed.
   */
  public function updateModules(array $module_list, array $module_filenames = array()) {
    $this->newModuleList = $module_list;
    foreach ($module_filenames as $module => $filename) {
      $this->moduleData[$module] = (object) array('uri' => $filename);
    }
    // If we haven't yet booted, we don't need to do anything: the new module
    // list will take effect when boot() is called. If we have already booted,
    // then reboot in order to refresh the serviceProvider list and container.
    if ($this->booted) {
      $this->booted = FALSE;
      $this->boot();
    }
  }

  /**
   * Returns the classname based on environment and testing prefix.
   *
   * @return string
   *   The class name.
   */
  protected function getClassName() {
    $parts = array('service_container', $this->environment);
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
   * Returns the kernel parameters.
   *
   * @return array An array of kernel parameters
   */
  protected function getKernelParameters() {
    return array(
      'kernel.environment' => $this->environment,
    );
  }

  /**
   * Initializes the service container.
   */
  protected function initializeContainer() {
    $persist = $this->getServicesToPersist();
    // If we are rebuilding the kernel and we are in a request scope, store
    // request info so we can add them back after the rebuild.
    if (isset($this->container) && $this->container->hasScope('request')) {
      $request = $this->container->get('request');
    }
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
        $this->persistServices($persist);
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
    // Second, check if some other request -- for example on another web
    // frontend or during the installer -- changed the list of enabled modules.
    if (isset($this->container)) {
      // All namespaces must be registered before we attempt to use any service
      // from the container.
      $container_modules = $this->container->getParameter('container.modules');
      $namespaces_before = $this->classLoader->getPrefixes();
      $this->registerNamespaces($this->getModuleNamespaces($container_modules));

      // If 'container.modules' is wrong, the container must be rebuilt.
      if (!isset($this->moduleList)) {
        $this->moduleList = $this->container->get('config.factory')->get('system.module')->load()->get('enabled');
      }
      if (array_keys($this->moduleList) !== array_keys($container_modules)) {
        $persist = $this->getServicesToPersist();
        unset($this->container);
        // Revert the class loader to its prior state. However,
        // registerNamespaces() performs a merge rather than replace, so to
        // effectively remove erroneous registrations, we must replace them with
        // empty arrays.
        $namespaces_after = $this->classLoader->getPrefixes();
        $namespaces_before += array_fill_keys(array_diff(array_keys($namespaces_after), array_keys($namespaces_before)), array());
        $this->registerNamespaces($namespaces_before);
      }
    }

    if (!isset($this->container)) {
      $this->container = $this->buildContainer();
      $this->persistServices($persist);

      // The namespaces are marked as persistent, so objects like the annotated
      // class discovery still has the right object. We may have updated the
      // list of modules, so set it.
      if ($this->container->initialized('container.namespaces')) {
        $this->container->get('container.namespaces')->exchangeArray($this->container->getParameter('container.namespaces'));
      }

      if ($this->allowDumping) {
        $this->containerNeedsDumping = TRUE;
      }
    }

    $this->container->set('kernel', $this);

    // Set the class loader which was registered as a synthetic service.
    $this->container->set('class_loader', $this->classLoader);
    // If we have a request set it back to the new container.
    if (isset($request)) {
      $this->container->enterScope('request');
      $this->container->set('request', $request);
    }
    \Drupal::setContainer($this->container);
  }

  /**
   * Returns service instances to persist from an old container to a new one.
   */
  protected function getServicesToPersist() {
    $persist = array();
    if (isset($this->container)) {
      foreach ($this->container->getParameter('persistIds') as $id) {
        // It's pointless to persist services not yet initialized.
        if ($this->container->initialized($id)) {
          $persist[$id] = $this->container->get($id);
        }
      }
    }
    return $persist;
  }

  /**
   * Moves persistent service instances into a new container.
   */
  protected function persistServices(array $persist) {
    foreach ($persist as $id => $object) {
      // Do not override services already set() on the new container, for
      // example 'service_container'.
      if (!$this->container->initialized($id)) {
        $this->container->set($id, $object);
      }
    }
  }

  /**
   * Builds the service container.
   *
   * @return ContainerBuilder The compiled service container
   */
  protected function buildContainer() {
    $this->initializeServiceProviders();
    $container = $this->getContainerBuilder();
    $container->set('kernel', $this);
    $container->setParameter('container.service_providers', $this->serviceProviderClasses);
    $container->setParameter('container.modules', $this->getModuleFileNames());

    // Get a list of namespaces and put it onto the container.
    $namespaces = $this->getModuleNamespaces($this->getModuleFileNames());
    // Add all components in \Drupal\Core and \Drupal\Component that have a
    // Plugin directory.
    foreach (array('Core', 'Component') as $parent_directory) {
      $path = DRUPAL_ROOT . '/core/lib/Drupal/' . $parent_directory;
      foreach (new \DirectoryIterator($path) as $component) {
        if (!$component->isDot() && is_dir($component->getPathname() . '/Plugin')) {
          $namespaces['Drupal\\' . $parent_directory  .'\\' . $component->getFilename()] = DRUPAL_ROOT . '/core/lib';
        }
      }
    }
    $container->setParameter('container.namespaces', $namespaces);

    // Register synthetic services.
    $container->register('class_loader')->setSynthetic(TRUE);
    $container->register('kernel', 'Symfony\Component\HttpKernel\KernelInterface')->setSynthetic(TRUE);
    $container->register('service_container', 'Symfony\Component\DependencyInjection\ContainerInterface')->setSynthetic(TRUE);
    $yaml_loader = new YamlFileLoader($container);
    foreach ($this->serviceYamls as $filename) {
      $yaml_loader->load($filename);
    }
    foreach ($this->serviceProviders as $provider) {
      $provider->register($container);
    }

    // Identify all services whose instances should be persisted when rebuilding
    // the container during the lifetime of the kernel (e.g., during a kernel
    // reboot). Include synthetic services, because by definition, they cannot
    // be automatically reinstantiated. Also include services tagged to persist.
    $persist_ids = array();
    foreach ($container->getDefinitions() as $id => $definition) {
      if ($definition->isSynthetic() || $definition->getTag('persist')) {
        $persist_ids[] = $id;
      }
    }
    $container->setParameter('persistIds', $persist_ids);

    $container->compile();
    return $container;
  }

  /**
   * Registers all service providers to the kernel.
   *
   * @throws \LogicException
   */
  protected function initializeServiceProviders() {
    $this->serviceProviders = array();

    foreach ($this->discoverServiceProviders() as $name => $provider) {
      if (isset($this->serviceProviders[$name])) {
        throw new \LogicException(sprintf('Trying to register two service providers with the same name "%s"', $name));
      }
      $this->serviceProviders[$name] = $provider;
    }
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
   * Gets a http kernel from the container
   *
   * @return HttpKernel
   */
  protected function getHttpKernel() {
    return $this->container->get('http_kernel');
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

  /**
   * Returns the file name for each enabled module.
   */
  protected function getModuleFileNames() {
    $filenames = array();
    foreach ($this->moduleList as $module => $weight) {
      if ($data = $this->moduleData($module)) {
        $filenames[$module] = $data->uri;
      }
    }
    return $filenames;
  }

  /**
   * Gets the namespaces of each enabled module.
   */
  protected function getModuleNamespaces($moduleFileNames) {
    $namespaces = array();
    foreach ($moduleFileNames as $module => $filename) {
      $namespaces["Drupal\\$module"] = DRUPAL_ROOT . '/' . dirname($filename) . '/lib';
    }
    return $namespaces;
  }

  /**
   * Registers a list of namespaces.
   */
  protected function registerNamespaces(array $namespaces = array()) {
    foreach ($namespaces as $prefix => $path) {
      $this->classLoader->add($prefix, $path);
    }
  }
}
