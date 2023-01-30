<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Extension\ConfigImportModuleUninstallValidatorInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Installer\InstallerKernel;

/**
 * Config import subscriber for config import events.
 */
class ConfigImportSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * Theme data.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $themeData;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The uninstall validators.
   *
   * @var \Drupal\Core\Extension\ModuleUninstallValidatorInterface[]
   */
  protected $uninstallValidators = [];

  /**
   * Constructs the ConfigImportSubscriber.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ModuleExtensionList $extension_list_module) {
    $this->themeHandler = $theme_handler;
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * Adds a module uninstall validator.
   *
   * @param \Drupal\Core\Extension\ModuleUninstallValidatorInterface $uninstall_validator
   *   The uninstall validator to add.
   */
  public function addUninstallValidator(ModuleUninstallValidatorInterface $uninstall_validator): void {
    $this->uninstallValidators[] = $uninstall_validator;
  }

  /**
   * Validates the configuration to be imported.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The Event to process.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    foreach (['delete', 'create', 'update'] as $op) {
      foreach ($event->getConfigImporter()->getUnprocessedConfiguration($op) as $name) {
        try {
          Config::validateName($name);
        }
        catch (ConfigNameException $e) {
          $message = $this->t('The config name @config_name is invalid.', ['@config_name' => $name]);
          $event->getConfigImporter()->logError($message);
        }
      }
    }
    $config_importer = $event->getConfigImporter();
    if ($config_importer->getStorageComparer()->getSourceStorage()->exists('core.extension')) {
      $this->validateModules($config_importer);
      $this->validateThemes($config_importer);
      $this->validateDependencies($config_importer);
    }
    else {
      $config_importer->logError($this->t('The core.extension configuration does not exist.'));
    }
  }

  /**
   * Validates module installations and uninstallations.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The configuration importer.
   */
  protected function validateModules(ConfigImporter $config_importer) {
    $core_extension = $config_importer->getStorageComparer()->getSourceStorage()->read('core.extension');

    // Get the install profile from the site's configuration.
    $current_core_extension = $config_importer->getStorageComparer()->getTargetStorage()->read('core.extension');
    $install_profile = $current_core_extension['profile'] ?? NULL;

    // Ensure the profile is not changing.
    if ($install_profile !== $core_extension['profile']) {
      if (InstallerKernel::installationAttempted()) {
        $config_importer->logError($this->t('The selected installation profile %install_profile does not match the profile stored in configuration %config_profile.', [
          '%install_profile' => $install_profile,
          '%config_profile' => $core_extension['profile'],
        ]));
        // If this error has occurred the other checks are irrelevant.
        return;
      }
      else {
        $config_importer->logError($this->t('Cannot change the install profile from %profile to %new_profile once Drupal is installed.', [
          '%profile' => $install_profile,
          '%new_profile' => $core_extension['profile'],
        ]));
      }
    }

    // Get a list of modules with dependency weights as values.
    $module_data = $this->moduleExtensionList->getList();
    $nonexistent_modules = array_keys(array_diff_key($core_extension['module'], $module_data));
    foreach ($nonexistent_modules as $module) {
      $config_importer->logError($this->t('Unable to install the %module module since it does not exist.', ['%module' => $module]));
    }

    // Ensure that all modules being installed have their dependencies met.
    $installs = $config_importer->getExtensionChangelist('module', 'install');
    foreach ($installs as $module) {
      $missing_dependencies = [];
      foreach (array_keys($module_data[$module]->requires) as $required_module) {
        if (!isset($core_extension['module'][$required_module])) {
          $missing_dependencies[] = $module_data[$required_module]->info['name'];
        }
      }
      if (!empty($missing_dependencies)) {
        $module_name = $module_data[$module]->info['name'];
        $message = $this->formatPlural(count($missing_dependencies),
          'Unable to install the %module module since it requires the %required_module module.',
          'Unable to install the %module module since it requires the %required_module modules.',
          ['%module' => $module_name, '%required_module' => implode(', ', $missing_dependencies)]
        );
        $config_importer->logError($message);
      }
    }

    // Ensure that all modules being uninstalled are not required by modules
    // that will be installed after the import.
    $uninstalls = $config_importer->getExtensionChangelist('module', 'uninstall');
    foreach ($uninstalls as $module) {
      foreach (array_keys($module_data[$module]->required_by) as $dependent_module) {
        if ($module_data[$dependent_module]->status && !in_array($dependent_module, $uninstalls, TRUE) && $dependent_module !== $install_profile) {
          $module_name = $module_data[$module]->info['name'];
          $dependent_module_name = $module_data[$dependent_module]->info['name'];
          $config_importer->logError($this->t('Unable to uninstall the %module module since the %dependent_module module is installed.', ['%module' => $module_name, '%dependent_module' => $dependent_module_name]));
        }
      }
      // Ensure that modules can be uninstalled.
      foreach ($this->uninstallValidators as $validator) {
        $reasons = $validator instanceof ConfigImportModuleUninstallValidatorInterface ?
          $validator->validateConfigImport($module, $config_importer->getStorageComparer()->getSourceStorage()) :
          $validator->validate($module);
        foreach ($reasons as $reason) {
          $config_importer->logError($this->t('Unable to uninstall the %module module because: @reason.',
            ['%module' => $module_data[$module]->info['name'], '@reason' => $reason]));
        }
      }
    }

    // Ensure that the install profile is not being uninstalled.
    if (in_array($install_profile, $uninstalls, TRUE)) {
      $profile_name = $module_data[$install_profile]->info['name'];
      $config_importer->logError($this->t('Unable to uninstall the %profile profile since it is the install profile.', ['%profile' => $profile_name]));
    }
  }

  /**
   * Validates theme installations and uninstallations.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The configuration importer.
   */
  protected function validateThemes(ConfigImporter $config_importer) {
    $core_extension = $config_importer->getStorageComparer()->getSourceStorage()->read('core.extension');
    // Get all themes including those that are not installed.
    $theme_data = $this->getThemeData();
    $module_data = $this->moduleExtensionList->getList();
    $nonexistent_themes = array_keys(array_diff_key($core_extension['theme'], $theme_data));
    foreach ($nonexistent_themes as $theme) {
      $config_importer->logError($this->t('Unable to install the %theme theme since it does not exist.', ['%theme' => $theme]));
    }

    // Ensure that all themes being installed have their dependencies met.
    $installs = $config_importer->getExtensionChangelist('theme', 'install');
    foreach ($installs as $theme) {
      $module_dependencies = $theme_data[$theme]->module_dependencies;
      // $theme_data[$theme]->requires contains both theme and module
      // dependencies keyed by the extension machine names.
      // $theme_data[$theme]->module_dependencies contains only the module
      // dependencies keyed by the module extension machine name. Therefore, we
      // can find the theme dependencies by finding array keys for 'requires'
      // that are not in $module_dependencies.
      $theme_dependencies = array_diff_key($theme_data[$theme]->requires, $module_dependencies);
      foreach (array_keys($theme_dependencies) as $required_theme) {
        if (!isset($core_extension['theme'][$required_theme])) {
          $theme_name = $theme_data[$theme]->info['name'];
          $required_theme_name = $theme_data[$required_theme]->info['name'];
          $config_importer->logError($this->t('Unable to install the %theme theme since it requires the %required_theme theme.', ['%theme' => $theme_name, '%required_theme' => $required_theme_name]));
        }
      }
      foreach (array_keys($module_dependencies) as $required_module) {
        if (!isset($core_extension['module'][$required_module])) {
          $theme_name = $theme_data[$theme]->info['name'];
          $required_module_name = $module_data[$required_module]->info['name'];
          $config_importer->logError($this->t('Unable to install the %theme theme since it requires the %required_module module.', ['%theme' => $theme_name, '%required_module' => $required_module_name]));
        }
      }
    }

    // Ensure that all themes being uninstalled are not required by themes that
    // will be installed after the import.
    $uninstalls = $config_importer->getExtensionChangelist('theme', 'uninstall');
    foreach ($uninstalls as $theme) {
      foreach (array_keys($theme_data[$theme]->required_by) as $dependent_theme) {
        if ($theme_data[$dependent_theme]->status && !in_array($dependent_theme, $uninstalls, TRUE)) {
          $theme_name = $theme_data[$theme]->info['name'];
          $dependent_theme_name = $theme_data[$dependent_theme]->info['name'];
          $config_importer->logError($this->t('Unable to uninstall the %theme theme since the %dependent_theme theme is installed.', ['%theme' => $theme_name, '%dependent_theme' => $dependent_theme_name]));
        }
      }
    }
  }

  /**
   * Validates configuration being imported does not have unmet dependencies.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The configuration importer.
   */
  protected function validateDependencies(ConfigImporter $config_importer) {
    $core_extension = $config_importer->getStorageComparer()->getSourceStorage()->read('core.extension');
    $existing_dependencies = [
      'config' => $config_importer->getStorageComparer()->getSourceStorage()->listAll(),
      'module' => array_keys($core_extension['module']),
      'theme' => array_keys($core_extension['theme']),
    ];

    $theme_data = $this->getThemeData();
    $module_data = $this->moduleExtensionList->getList();

    // Validate the dependencies of all the configuration. We have to validate
    // the entire tree because existing configuration might depend on
    // configuration that is being deleted.
    foreach ($config_importer->getStorageComparer()->getSourceStorage()->listAll() as $name) {
      // Ensure that the config owner is installed. This checks all
      // configuration including configuration entities.
      [$owner] = explode('.', $name, 2);
      if ($owner !== 'core') {
        $message = FALSE;
        if (!isset($core_extension['module'][$owner]) && isset($module_data[$owner])) {
          $message = $this->t('Configuration %name depends on the %owner module that will not be installed after import.', [
            '%name' => $name,
            '%owner' => $module_data[$owner]->info['name'],
          ]);
        }
        elseif (!isset($core_extension['theme'][$owner]) && isset($theme_data[$owner])) {
          $message = $this->t('Configuration %name depends on the %owner theme that will not be installed after import.', [
            '%name' => $name,
            '%owner' => $theme_data[$owner]->info['name'],
          ]);
        }
        elseif (!isset($core_extension['module'][$owner]) && !isset($core_extension['theme'][$owner])) {
          $message = $this->t('Configuration %name depends on the %owner extension that will not be installed after import.', [
            '%name' => $name,
            '%owner' => $owner,
          ]);
        }

        if ($message) {
          $config_importer->logError($message);
          continue;
        }
      }

      $data = $config_importer->getStorageComparer()->getSourceStorage()->read($name);
      // Configuration entities have dependencies on modules, themes, and other
      // configuration entities that we can validate. Their content dependencies
      // are not validated since we assume that they are soft dependencies.
      // Configuration entities can be identified by having 'dependencies' and
      // 'uuid' keys.
      if (isset($data['dependencies']) && isset($data['uuid'])) {
        $dependencies_to_check = array_intersect_key($data['dependencies'], array_flip(['module', 'theme', 'config']));
        foreach ($dependencies_to_check as $type => $dependencies) {
          $diffs = array_diff($dependencies, $existing_dependencies[$type]);
          if (!empty($diffs)) {
            $message = FALSE;
            switch ($type) {
              case 'module':
                $message = $this->formatPlural(
                  count($diffs),
                  'Configuration %name depends on the %module module that will not be installed after import.',
                  'Configuration %name depends on modules (%module) that will not be installed after import.',
                  ['%name' => $name, '%module' => implode(', ', $this->getNames($diffs, $module_data))]
                );
                break;

              case 'theme':
                $message = $this->formatPlural(
                  count($diffs),
                  'Configuration %name depends on the %theme theme that will not be installed after import.',
                  'Configuration %name depends on themes (%theme) that will not be installed after import.',
                  ['%name' => $name, '%theme' => implode(', ', $this->getNames($diffs, $theme_data))]
                );
                break;

              case 'config':
                $message = $this->formatPlural(
                  count($diffs),
                  'Configuration %name depends on the %config configuration that will not exist after import.',
                  'Configuration %name depends on configuration (%config) that will not exist after import.',
                  ['%name' => $name, '%config' => implode(', ', $diffs)]
                );
                break;
            }

            if ($message) {
              $config_importer->logError($message);
            }
          }
        }
      }
    }
  }

  /**
   * Gets theme data.
   *
   * @return \Drupal\Core\Extension\Extension[]
   */
  protected function getThemeData() {
    if (!isset($this->themeData)) {
      $this->themeData = $this->themeHandler->rebuildThemeData();
    }
    return $this->themeData;
  }

  /**
   * Gets human readable extension names.
   *
   * @param array $names
   *   A list of extension machine names.
   * @param \Drupal\Core\Extension\Extension[] $extension_data
   *   Extension data.
   *
   * @return array
   *   A list of human-readable extension names, or machine names if
   *   human-readable names are not available.
   */
  protected function getNames(array $names, array $extension_data) {
    return array_map(function ($name) use ($extension_data) {
      if (isset($extension_data[$name])) {
        $name = $extension_data[$name]->info['name'];
      }
      return $name;
    }, $names);
  }

}
