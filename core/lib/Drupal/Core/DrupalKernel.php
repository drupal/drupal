<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\CoreServiceProvider;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\YamlFileLoader;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Language\Language;
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
   * List of available modules and installation profiles.
   *
   * @var \Drupal\Core\Extension\Extension[]
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
   * Whether the container can be dumped.
   *
   * @var bool
   */
  protected $allowDumping;

  /**
   * Whether the container can be loaded.
   *
   * @var bool
   */
  protected $allowLoading;

  /**
   * Whether the container needs to be dumped once booting is complete.
   *
   * @var bool
   */
  protected $containerNeedsDumping;

  /**
   * List of discovered services.yml pathnames.
   *
   * This is a nested array whose top-level keys are 'app' and 'site', denoting
   * the origin of a service provider. Site-specific providers have to be
   * collected separately, because they need to be processed last, so as to be
   * able to override services from application service providers.
   *
   * @var array
   */
  protected $serviceYamls;

  /**
   * List of discovered service provider class names.
   *
   * This is a nested array whose top-level keys are 'app' and 'site', denoting
   * the origin of a service provider. Site-specific providers have to be
   * collected separately, because they need to be processed last, so as to be
   * able to override services from application service providers.
   *
   * @var array
   */
  protected $serviceProviderClasses;

  /**
   * List of instantiated service provider classes.
   *
   * @see \Drupal\Core\DrupalKernel::$serviceProviderClasses
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
   * @param bool $allow_loading
   *   (optional) FALSE to prevent the kernel attempting to read the dependency
   *   injection container from disk. Defaults to $allow_dumping.
   */
  public function __construct($environment, ClassLoader $class_loader, $allow_dumping = TRUE, $allow_loading = NULL) {
    $this->environment = $environment;
    $this->booted = FALSE;
    $this->classLoader = $class_loader;
    $this->allowDumping = $allow_dumping;
    $this->allowLoading = isset($allow_loading) ? $allow_loading : $allow_dumping;
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
    $this->container = NULL;
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
    $this->serviceYamls = array(
      'app' => array(),
      'site' => array(),
    );
    $this->serviceProviderClasses = array(
      'app' => array(),
      'site' => array(),
    );
    $this->serviceYamls['app']['core'] = 'core/core.services.yml';
    $this->serviceProviderClasses['app']['core'] = 'Drupal\Core\CoreServiceProvider';

    // Retrieve enabled modules and register their namespaces.
    if (!isset($this->moduleList)) {
      $extensions = $this->getConfigStorage()->read('core.extension');
      $this->moduleList = isset($extensions['module']) ? $extensions['module'] : array();
    }
    $module_filenames = $this->getModuleFileNames();
    $this->registerNamespacesPsr4($this->getModuleNamespacesPsr4($module_filenames));

    // Load each module's serviceProvider class.
    foreach ($this->moduleList as $module => $weight) {
      $camelized = ContainerBuilder::camelize($module);
      $name = "{$camelized}ServiceProvider";
      $class = "Drupal\\{$module}\\{$name}";
      if (class_exists($class)) {
        $this->serviceProviderClasses['app'][$module] = $class;
      }
      $filename = dirname($module_filenames[$module]) . "/$module.services.yml";
      if (file_exists($filename)) {
        $this->serviceYamls['app'][$module] = $filename;
      }
    }

    // Add site-specific service providers.
    if (!empty($GLOBALS['conf']['container_service_providers'])) {
      foreach ($GLOBALS['conf']['container_service_providers'] as $class) {
        if (class_exists($class)) {
          $this->serviceProviderClasses['site'][] = $class;
        }
      }
    }
    if (!empty($GLOBALS['conf']['container_yamls'])) {
      $this->serviceYamls['site'] = $GLOBALS['conf']['container_yamls'];
    }
    if (file_exists($site_services_yml = conf_path() . '/services.yml')) {
      $this->serviceYamls['site'][] = $site_services_yml;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceProviders($origin) {
    return $this->serviceProviders[$origin];
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
  public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = TRUE) {
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
   * @return \Drupal\Core\Extension\Extension|bool
   *   Returns an Extension object if the module is found, FALSE otherwise.
   */
  protected function moduleData($module) {
    if (!$this->moduleData) {
      // First, find profiles.
      $listing = new ExtensionDiscovery();
      $listing->setProfileDirectories(array());
      $all_profiles = $listing->scan('profile');
      $profiles = array_intersect_key($all_profiles, $this->moduleList);

      // If a module is within a profile directory but specifies another
      // profile for testing, it needs to be found in the parent profile.
      $settings = $this->getConfigStorage()->read('simpletest.settings');
      $parent_profile = !empty($settings['parent_profile']) ? $settings['parent_profile'] : NULL;
      if ($parent_profile && !isset($profiles[$parent_profile])) {
        // In case both profile directories contain the same extension, the
        // actual profile always has precedence.
        $profiles = array($parent_profile => $all_profiles[$parent_profile]) + $profiles;
      }

      $profile_directories = array_map(function ($profile) {
        return $profile->getPath();
      }, $profiles);
      $listing->setProfileDirectories($profile_directories);

      // Now find modules.
      $this->moduleData = $profiles + $listing->scan('module');
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
    foreach ($module_filenames as $name => $extension) {
      $this->moduleData[$name] = $extension;
    }

    // This method is called whenever the list of modules changed. Therefore
    // disable loading of a dumped container from the disk, because it is
    // guaranteed to be out of date and needs to be rebuilt anyway.
    $this->allowLoading = FALSE;

    // If we haven't yet booted, we don't need to do anything: the new module
    // list will take effect when boot() is called. If we have already booted,
    // then reboot in order to refresh the serviceProvider list and container.
    if ($this->booted) {
      $this->booted = FALSE;
      $this->boot();
    }
  }

  /**
   * Returns the classname based on environment.
   *
   * @return string
   *   The class name.
   */
  protected function getClassName() {
    $parts = array('service_container', $this->environment);
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
    $this->containerNeedsDumping = FALSE;
    $persist = $this->getServicesToPersist();
    // The request service requires custom persisting logic, since it is also
    // potentially scoped.
    $request_scope = FALSE;
    if (isset($this->container)) {
      if ($this->container->isScopeActive('request')) {
        $request_scope = TRUE;
      }
      if ($this->container->initialized('request')) {
        $request = $this->container->get('request');
      }
    }
    $this->container = NULL;
    $class = $this->getClassName();
    $cache_file = $class . '.php';

    if ($this->allowLoading) {
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
    if (isset($this->container)) {
      // All namespaces must be registered before we attempt to use any service
      // from the container.
      $this->registerNamespacesPsr4($this->container->getParameter('container.namespaces'));
    }
    else {
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
    if ($request_scope) {
      $this->container->enterScope('request');
    }
    if (isset($request)) {
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
    $container->setParameter('container.modules', $this->getModulesParameter());

    // Get a list of namespaces and put it onto the container.
    $namespaces = $this->getModuleNamespacesPsr4($this->getModuleFileNames());
    // Add all components in \Drupal\Core and \Drupal\Component that have a
    // Plugin directory.
    foreach (array('Core', 'Component') as $parent_directory) {
      $path = DRUPAL_ROOT . '/core/lib/Drupal/' . $parent_directory;
      $parent_namespace = 'Drupal\\' . $parent_directory;
      foreach (new \DirectoryIterator($path) as $component) {
        if (!$component->isDot() && $component->isDir() && is_dir($component->getPathname() . '/Plugin')) {
          $namespaces[$parent_namespace . '\\' . $component->getFilename()] = $path . '/' . $component->getFilename();
        }
      }
    }
    $container->setParameter('container.namespaces', $namespaces);

    // Store the default language values on the container. This is so that the
    // default language can be configured using the configuration factory. This
    // avoids the circular dependencies that would created by
    // \Drupal\language\LanguageServiceProvider::alter() and allows the default
    // language to not be English in the installer.
    $default_language_values = Language::$defaultValues;
    if ($system = $this->getConfigStorage()->read('system.site')) {
      if ($default_language_values['id'] != $system['langcode']) {
        $default_language_values = array('id' => $system['langcode'], 'default' => TRUE);
      }
    }
    $container->setParameter('language.default_values', $default_language_values);

    // Register synthetic services.
    $container->register('class_loader')->setSynthetic(TRUE);
    $container->register('kernel', 'Symfony\Component\HttpKernel\KernelInterface')->setSynthetic(TRUE);
    $container->register('service_container', 'Symfony\Component\DependencyInjection\ContainerInterface')->setSynthetic(TRUE);

    // Register application services.
    $yaml_loader = new YamlFileLoader($container);
    foreach ($this->serviceYamls['app'] as $filename) {
      $yaml_loader->load($filename);
    }
    foreach ($this->serviceProviders['app'] as $provider) {
      if ($provider instanceof ServiceProviderInterface) {
        $provider->register($container);
      }
    }
    // Register site-specific service overrides.
    foreach ($this->serviceYamls['site'] as $filename) {
      $yaml_loader->load($filename);
    }
    foreach ($this->serviceProviders['site'] as $provider) {
      if ($provider instanceof ServiceProviderInterface) {
        $provider->register($container);
      }
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
    $this->discoverServiceProviders();
    $this->serviceProviders = array(
      'app' => array(),
      'site' => array(),
    );
    foreach ($this->serviceProviderClasses as $origin => $classes) {
      foreach ($classes as $name => $class) {
        $this->serviceProviders[$origin][$name] = new $class;
      }
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
   * Returns the active configuration storage to use during building the container.
   *
   * @return \Drupal\Core\Config\StorageInterface
   */
  protected function getConfigStorage() {
    if (!isset($this->configStorage)) {
      // The active configuration storage may not exist yet; e.g., in the early
      // installer. Catch the exception thrown by config_get_config_directory().
      try {
        $this->configStorage = BootstrapConfigStorageFactory::get();
      }
      catch (\Exception $e) {
        $this->configStorage = new NullStorage();
      }
    }
    return $this->configStorage;
  }

  /**
   * Returns an array of Extension class parameters for all enabled modules.
   *
   * @return array
   */
  protected function getModulesParameter() {
    $extensions = array();
    foreach ($this->moduleList as $name => $weight) {
      if ($data = $this->moduleData($name)) {
        $extensions[$name] = array(
          'type' => $data->getType(),
          'pathname' => $data->getPathname(),
          'filename' => $data->getExtensionFilename(),
        );
      }
    }
    return $extensions;
  }

  /**
   * Gets the file name for each enabled module.
   *
   * @return array
   *   Array where each key is a module name, and each value is a path to the
   *   respective *.module or *.profile file.
   */
  protected function getModuleFileNames() {
    $filenames = array();
    foreach ($this->moduleList as $module => $weight) {
      if ($data = $this->moduleData($module)) {
        $filenames[$module] = $data->getPathname();
      }
    }
    return $filenames;
  }

  /**
   * Gets the PSR-4 base directories for module namespaces.
   *
   * @param array $module_file_names
   *   Array where each key is a module name, and each value is a path to the
   *   respective *.module or *.profile file.
   *
   * @return array
   *   Array where each key is a module namespace like 'Drupal\system', and each
   *   value is an array of PSR-4 base directories associated with the module
   *   namespace.
   */
  protected function getModuleNamespacesPsr4($module_file_names) {
    $namespaces = array();
    foreach ($module_file_names as $module => $filename) {
      // @todo Remove lib/Drupal/$module, once the switch to PSR-4 is complete.
      $namespaces["Drupal\\$module"][] = DRUPAL_ROOT . '/' . dirname($filename) . '/lib/Drupal/' . $module;
      $namespaces["Drupal\\$module"][] = DRUPAL_ROOT . '/' . dirname($filename) . '/src';
    }
    return $namespaces;
  }

  /**
   * Gets the PSR-0 base directories for module namespaces.
   *
   * @param array $module_file_names
   *   Array where each key is a module name, and each value is a path to the
   *   respective *.module or *.profile file.
   *
   * @return array
   *   Array where each key is a module namespace like 'Drupal\system', and each
   *   value is a PSR-0 base directory associated with the module namespace.
   */
  protected function getModuleNamespaces($module_file_names) {
    $namespaces = array();
    foreach ($module_file_names as $module => $filename) {
      $namespaces["Drupal\\$module"] = DRUPAL_ROOT . '/' . dirname($filename) . '/lib';
    }
    return $namespaces;
  }

  /**
   * Registers a list of namespaces with PSR-4 directories for class loading.
   *
   * @param array $namespaces
   *   Array where each key is a namespace like 'Drupal\system', and each value
   *   is either a PSR-4 base directory, or an array of PSR-4 base directories
   *   associated with this namespace.
   */
  protected function registerNamespacesPsr4(array $namespaces = array()) {
    foreach ($namespaces as $prefix => $paths) {
      $this->classLoader->addPsr4($prefix . '\\', $paths);
    }
  }

  /**
   * Registers a list of namespaces with PSR-0 directories for class loading.
   *
   * @param array $namespaces
   *   Array where each key is a namespace like 'Drupal\system', and each value
   *   is either a PSR-0 base directory, or an array of PSR-0 base directories
   *   associated with this namespace.
   */
  protected function registerNamespaces(array $namespaces = array()) {
    foreach ($namespaces as $prefix => $path) {
      $this->classLoader->add($prefix, $path);
    }
  }
}
