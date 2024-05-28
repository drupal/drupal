<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Config\StorageInterface;

/**
 * Special interface for module uninstall validators for configuration import.
 *
 * A module uninstall validator that needs different functionality prior to a
 * configuration import should implement this interface and be defined in
 * a Drupal @link container service @endlink that is tagged
 * module_install.uninstall_validator. If autoconfiguration is enabled, the
 * service will be automatically tagged.
 */
interface ConfigImportModuleUninstallValidatorInterface extends ModuleUninstallValidatorInterface {

  /**
   * Determines reasons a module can not be uninstalled prior to config import.
   *
   * @param string $module
   *   A module name.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   Storage object used to read configuration that is about to be imported.
   *
   * @return string[]
   *   An array of reasons the module can not be uninstalled, empty if it can.
   *   Each reason should not end with any punctuation since multiple reasons
   *   can be displayed together.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber::validateModules()
   */
  public function validateConfigImport(string $module, StorageInterface $source_storage): array;

}
