<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\InstallerKernel.
 */

namespace Drupal\Core\Installer;

use Drupal\Core\DrupalKernel;

/**
 * Extend DrupalKernel to handle force some kernel behaviors.
 */
class InstallerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   *
   * @param bool $rebuild
   *   Force a container rebuild. Unlike the parent method, this defaults to
   *   TRUE.
   */
  protected function initializeContainer($rebuild = TRUE) {
    $container = parent::initializeContainer($rebuild);
    return $container;
  }

  /**
   * Reset the bootstrap config storage.
   *
   * Use this from a database driver runTasks() if the method overrides the
   * bootstrap config storage. Normally the bootstrap config storage is not
   * re-instantiated during a single install request. Most drivers will not
   * need this method.
   *
   * @see \Drupal\Core\Database\Install\Tasks::runTasks().
   */
  public function resetConfigStorage() {
    $this->configStorage = NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function addServiceFiles($service_yamls) {
    // In the beginning there is no settings.php and no service YAMLs.
    return parent::addServiceFiles($service_yamls ?: []);
  }
}
