<?php

namespace Drupal\Core\Extension;

/**
 * Common interface for module uninstall validators.
 *
 * A module uninstall validator must implement this interface and be defined in
 * a Drupal @link container service @endlink that is tagged
 * module_install.uninstall_validator. If autoconfiguration is enabled, the
 * service will be automatically tagged.
 *
 * Validators are called during module uninstall and prior to running a
 * configuration import. If different logic is required when uninstalling via
 * configuration import implement ConfigImportModuleUninstallValidatorInterface.
 *
 * @see \Drupal\Core\Extension\ModuleInstaller::validateUninstall()
 * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber::validateModules()
 * @see \Drupal\Core\Extension\ConfigImportModuleUninstallValidatorInterface
 */
interface ModuleUninstallValidatorInterface {

  /**
   * Determines the reasons a module can not be uninstalled.
   *
   * @param string $module
   *   A module name.
   *
   * @return string[]
   *   An array of reasons the module can not be uninstalled, empty if it can.
   *   Each reason should not end with any punctuation since multiple reasons
   *   can be displayed together.
   *
   * @see template_preprocess_system_modules_uninstall()
   */
  public function validate($module);

}
