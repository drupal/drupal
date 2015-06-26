<?php

/**
 * @file
 * Contains \Drupal\Core\Updater\Module.
 */

namespace Drupal\Core\Updater;

use Drupal\Core\Url;

/**
 * Defines a class for updating modules using
 * Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class Module extends Updater implements UpdaterInterface {

  /**
   * Returns the directory where a module should be installed.
   *
   * If the module is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * module, we always want it to go into /modules, since that's
   * where all the documentation recommends users install their modules, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new module if it's already
   * found on your system, and if there was a copy in the top-level we'd see it.
   *
   * @return string
   *   A directory path.
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('module', $this->name)) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'modules';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  /**
   * Implements Drupal\Core\Updater\UpdaterInterface::isInstalled().
   */
  public function isInstalled() {
    return (bool) drupal_get_path('module', $this->name);
  }

  /**
   * Implements Drupal\Core\Updater\UpdaterInterface::canUpdateDirectory().
   */
  public static function canUpdateDirectory($directory) {
    $info = static::getExtensionInfo($directory);

    return (isset($info['type']) && $info['type'] == 'module');
  }

  /**
   * Determines whether this class can update the specified project.
   *
   * @param string $project_name
   *   The project to check.
   *
   * @return bool
   */
  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('module', $project_name);
  }

  /**
   * Returns available database schema updates once a new version is installed.
   *
   * @return array
   */
  public function getSchemaUpdates() {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    require_once DRUPAL_ROOT . '/core/includes/update.inc';

    if (!self::canUpdate($this->name)) {
      return array();
    }
    module_load_include('install', $this->name);

    if (!$updates = drupal_get_schema_versions($this->name)) {
      return array();
    }
    $modules_with_updates = update_get_update_list();
    if ($updates = $modules_with_updates[$this->name]) {
      if ($updates['start']) {
        return $updates['pending'];
      }
    }
    return array();
  }

  /**
   * Overrides Drupal\Core\Updater\Updater::postInstallTasks().
   */
  public function postInstallTasks() {
    return array(
      \Drupal::l(t('Install another module'), new Url('update.module_install')),
      \Drupal::l(t('Enable newly added modules'), new Url('system.modules_list')),
      \Drupal::l(t('Administration pages'), new Url('system.admin')),
    );
  }

  /**
   * Overrides Drupal\Core\Updater\Updater::postUpdateTasks().
   */
  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }
}
