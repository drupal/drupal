<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ModuleInstallerInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Provides the installation of modules with creating the db schema and more.
 */
interface ModuleInstallerInterface {

  /**
   * Installs a given list of modules.
   *
   * Order of events:
   * - Gather and add module dependencies to $module_list (if applicable).
   * - For each module that is being installed:
   *   - Invoke hook_module_preinstall().
   *   - Install module schema and update system registries and caches.
   *   - Invoke hook_install() and add it to the list of installed modules.
   * - Invoke hook_modules_installed().
   *
   * To install test modules add
   * @code
   * $settings['extension_discovery_scan_tests'] = TRUE;
   * @encode
   * to your settings.php.
   *
   * @param string[] $module_list
   *   An array of module names.
   * @param bool $enable_dependencies
   *   (optional) If TRUE, dependencies will automatically be installed in the
   *   correct order. This incurs a significant performance cost, so use FALSE
   *   if you know $module_list is already complete.
   *
   * @return bool
   *   TRUE if the modules were successfully installed.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown when a requested module, or a dependency of one, can not be found.
   *
   * @see hook_module_preinstall()
   * @see hook_install()
   * @see hook_modules_installed()
   */
  public function install(array $module_list, $enable_dependencies = TRUE);

  /**
   * Uninstalls a given list of modules.
   *
   * @param string[] $module_list
   *   The modules to uninstall.
   * @param bool $uninstall_dependents
   *   (optional) If TRUE, dependent modules will automatically be uninstalled
   *   in the correct order. This incurs a significant performance cost, so use
   *   FALSE if you know $module_list is already complete.
   *
   * @return bool
   *   FALSE if one or more dependencies are missing, TRUE otherwise.
   *
   * @see hook_module_preuninstall()
   * @see hook_uninstall()
   * @see hook_modules_uninstalled()
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE);

  /**
   * Adds module a uninstall validator.
   *
   * @param \Drupal\Core\Extension\ModuleUninstallValidatorInterface $uninstall_validator
   *   The uninstall validator to add.
   */
  public function addUninstallValidator(ModuleUninstallValidatorInterface $uninstall_validator);

  /**
   * Determines whether a list of modules can be uninstalled.
   *
   * @param string[] $module_list
   *   An array of module names.
   *
   * @return string[]
   *   An array of reasons the module can not be uninstalled, empty if it can.
   */
  public function validateUninstall(array $module_list);

}

