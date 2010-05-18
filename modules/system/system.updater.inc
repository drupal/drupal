<?php
// $Id: system.updater.inc,v 1.8 2010/05/18 18:11:13 dries Exp $

/**
 * @file
 * Subclasses of the Updater class to update Drupal core knows how to update.
 * At this time, only modules and themes are supported.
 */

/**
 * Class for updating modules using FileTransfer classes via authorize.php.
 */
class ModuleUpdater extends Updater implements DrupalUpdaterInterface {

  /**
   * Return the directory where a module should be installed.
   *
   * If the module is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * module, we always want it to go into sites/all/modules, since that's
   * where all the documentation recommends users install their modules, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new module if it's already
   * found on your system, and if there was a copy in sites/all, we'd see it.
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('module', $this->name)) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'sites/all/modules';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  public function isInstalled() {
    return (bool) drupal_get_path('module', $this->name);
  }

  public static function canUpdateDirectory($directory) {
    if (file_scan_directory($directory, '/.*\.module/')) {
      return TRUE;
    }
    return FALSE;
  }

  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('module', $project_name);
  }

  /**
   * Return available database schema updates one a new version is installed.
   */
  public function getSchemaUpdates() {
    require_once DRUPAL_ROOT . '/includes/install.inc';
    require_once DRUPAL_ROOT . '/includes/update.inc';

    if (_update_get_project_type($project) != 'module') {
      return array();
    }
    module_load_include('install', $project);

    if (!$updates = drupal_get_schema_versions($project)) {
      return array();
    }
    $updates_to_run = array();
    $modules_with_updates = update_get_update_list();
    if ($updates = $modules_with_updates[$project]) {
      if ($updates['start']) {
        return $updates['pending'];
      }
    }
    return array();
  }

  public function postInstallTasks() {
    return array(
      l(t('Enable newly added modules in !project', array('!project' => $this->title)), 'admin/modules'),
    );
  }

  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }

}

/**
 * Class for updating themes using FileTransfer classes via authorize.php.
 */
class ThemeUpdater extends Updater implements DrupalUpdaterInterface {

  /**
   * Return the directory where a theme should be installed.
   *
   * If the theme is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * theme, we always want it to go into sites/all/themes, since that's
   * where all the documentation recommends users install their themes, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new theme if it's already
   * found on your system, and if there was a copy in sites/all, we'd see it.
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('theme', $this->name)) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'sites/all/themes';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  public function isInstalled() {
    return (bool) drupal_get_path('theme', $this->name);
  }

  static function canUpdateDirectory($directory) {
    // This is a lousy test, but don't know how else to confirm it is a theme.
    if (file_scan_directory($directory, '/.*\.module/')) {
      return FALSE;
    }
    return TRUE;
  }

  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('theme', $project_name);
  }

  public function postInstall() {
    // Update the system table.
    clearstatcache();
    system_rebuild_theme_data();

    // Active the theme
    db_update('system')
      ->fields(array('status' => 1))
      ->condition('type', 'theme')
      ->condition('name', $this->name)
      ->execute();
  }

  public function postInstallTasks() {
    return array(
      l(t('Set the !project theme as default', array('!project' => $this->title)), 'admin/appearance'),
    );
  }
}
