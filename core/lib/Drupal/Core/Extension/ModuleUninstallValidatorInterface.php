<?php

namespace Drupal\Core\Extension;

/**
 * Common interface for module uninstall validators.
 *
 * A module uninstall validator must implement this interface and be defined in
 * a Drupal @link container service @endlink that is tagged
 * module_install.uninstall_validator.
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
