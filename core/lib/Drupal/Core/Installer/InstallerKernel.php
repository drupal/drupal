<?php

namespace Drupal\Core\Installer;

use Drupal\Core\DrupalKernel;

/**
 * Extend DrupalKernel to handle force some kernel behaviors.
 */
class InstallerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  protected function initializeContainer() {
    // Always force a container rebuild.
    $this->containerNeedsRebuild = TRUE;
    $container = parent::initializeContainer();
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
   * @see \Drupal\Core\Database\Install\Tasks::runTasks()
   */
  public function resetConfigStorage() {
    $this->configStorage = NULL;
  }

  /**
   * Returns the active configuration storage used during early install.
   *
   * This override changes the visibility so that the installer can access
   * config storage before the container is properly built.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getConfigStorage() {
    return parent::getConfigStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallProfile() {
    global $install_state;
    if ($install_state && empty($install_state['installation_finished'])) {
      // If the profile has been selected return it.
      if (isset($install_state['parameters']['profile'])) {
        $profile = $install_state['parameters']['profile'];
      }
      else {
        $profile = NULL;
      }
    }
    else {
      $profile = parent::getInstallProfile();
    }
    return $profile;
  }

  /**
   * Returns TRUE if a Drupal installation is currently being attempted.
   *
   * @return bool
   *   TRUE if the installation is currently being attempted.
   */
  public static function installationAttempted() {
    // This cannot rely on the MAINTENANCE_MODE constant, since that would
    // prevent tests from using the non-interactive installer, in which case
    // Drupal only happens to be installed within the same request, but
    // subsequently executed code does not involve the installer at all.
    // @see install_drupal()
    return isset($GLOBALS['install_state']) && empty($GLOBALS['install_state']['installation_finished']);
  }

}
