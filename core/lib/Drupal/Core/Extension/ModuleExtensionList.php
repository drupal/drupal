<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Update\UpdateHookRegistry;

/**
 * Provides a list of available modules.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
class ModuleExtensionList extends ExtensionList {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'dependencies' => [],
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => \Drupal::MINIMUM_PHP,
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The profile list needed by this module list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $profileList;

  /**
   * Constructs a new ModuleExtensionList instance.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The extension type.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ExtensionList $profile_list
   *   The site profile listing.
   * @param string $install_profile
   *   The install profile used by the site.
   * @param array[] $container_modules_info
   *   (optional) The module locations coming from the compiled container.
   */
  public function __construct($root, $type, CacheBackendInterface $cache, InfoParserInterface $info_parser, ModuleHandlerInterface $module_handler, StateInterface $state, ConfigFactoryInterface $config_factory, ExtensionList $profile_list, $install_profile, array $container_modules_info = []) {
    parent::__construct($root, $type, $cache, $info_parser, $module_handler, $state, $install_profile);

    $this->configFactory = $config_factory;
    $this->profileList = $profile_list;

    // Use the information from the container. This is an optimization.
    foreach ($container_modules_info as $module_name => $info) {
      $this->setPathname($module_name, $info['pathname']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionDiscovery() {
    $discovery = parent::getExtensionDiscovery();

    if ($this->getActiveProfile()) {
      $discovery->setProfileDirectories($this->getProfileDirectories($discovery));
    }

    return $discovery;
  }

  /**
   * Finds all installation profile paths.
   *
   * @param \Drupal\Core\Extension\ExtensionDiscovery $discovery
   *   The extension discovery.
   *
   * @return string[]
   *   Paths to all installation profiles.
   */
  protected function getProfileDirectories(ExtensionDiscovery $discovery) {
    $discovery->setProfileDirectories([]);
    $all_profiles = $discovery->scan('profile');
    $active_profile = $all_profiles[$this->installProfile];
    $profiles = array_intersect_key($all_profiles, $this->configFactory->get('core.extension')->get('module') ?: [$active_profile->getName() => 0]);

    $profile_directories = array_map(function (Extension $profile) {
      return $profile->getPath();
    }, $profiles);
    return $profile_directories;
  }

  /**
   * Gets the processed active profile object, or null.
   *
   * @return \Drupal\Core\Extension\Extension|null
   *   The active profile, if there is one.
   */
  protected function getActiveProfile() {
    $profiles = $this->profileList->getList();
    if ($this->installProfile && isset($profiles[$this->installProfile])) {
      return $profiles[$this->installProfile];
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  protected function doScanExtensions() {
    $extensions = parent::doScanExtensions();

    $profiles = $this->profileList->getList();
    // Modify the active profile object that was previously added to the module
    // list.
    if ($this->installProfile && isset($profiles[$this->installProfile])) {
      $extensions[$this->installProfile] = $profiles[$this->installProfile];
    }

    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  protected function doList() {
    // Find modules.
    $extensions = parent::doList();
    // It is possible that a module was marked as required by
    // hook_system_info_alter() and modules that it depends on are not required.
    foreach ($extensions as $extension) {
      $this->ensureRequiredDependencies($extension, $extensions);
    }

    // Add status, weight, and schema version.
    $installed_modules = $this->configFactory->get('core.extension')->get('module') ?: [];
    foreach ($extensions as $name => $module) {
      $module->weight = $installed_modules[$name] ?? 0;
      $module->status = (int) isset($installed_modules[$name]);
      $module->schema_version = UpdateHookRegistry::SCHEMA_UNINSTALLED;
    }
    $extensions = $this->moduleHandler->buildModuleDependencies($extensions);

    if ($this->installProfile && $extensions[$this->installProfile]) {
      $active_profile = $extensions[$this->installProfile];

      // Installation profile hooks are always executed last.
      $active_profile->weight = 1000;

      // Installation profiles are hidden by default, unless explicitly
      // specified otherwise in the .info.yml file.
      if (!isset($active_profile->info['hidden'])) {
        $active_profile->info['hidden'] = TRUE;
      }

      // Add a default distribution name if the profile did not provide one.
      // @see install_profile_info()
      // @see drupal_install_profile_distribution_name()
      if (!isset($active_profile->info['distribution']['name'])) {
        $active_profile->info['distribution']['name'] = 'Drupal';
      }
    }

    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    return array_keys($this->moduleHandler->getModuleList());
  }

  /**
   * Marks dependencies of required modules as 'required', recursively.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   The module extension object.
   * @param \Drupal\Core\Extension\Extension[] $modules
   *   Extension objects for all available modules.
   */
  protected function ensureRequiredDependencies(Extension $module, array $modules = []) {
    if (!empty($module->info['required'])) {
      foreach ($module->info['dependencies'] as $dependency) {
        $dependency_name = Dependency::createFromString($dependency)->getName();
        if (!isset($modules[$dependency_name]->info['required'])) {
          $modules[$dependency_name]->info['required'] = TRUE;
          $modules[$dependency_name]->info['explanation'] = $this->t('Dependency of required module @module', ['@module' => $module->info['name']]);
          // Ensure any dependencies it has are required.
          $this->ensureRequiredDependencies($modules[$dependency_name], $modules);
        }
      }
    }
  }

}
