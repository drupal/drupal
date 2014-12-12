<?php

/**
 * @file
 * Contains Drupal\Core\Extension\ModuleUninstallValidatorInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Common interface for module uninstall validators.
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
   */
  public function validate($module);
}
