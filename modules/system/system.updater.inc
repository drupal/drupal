<?php
// $Id$

/**
 * @file
 * Subclasses of the Updater class to update Drupal core knows how to update.
 * At this time, only modules and themes are supported.
 */
 
/**
 * Class for updating modules using FileTransfer classes via authorize.php.
 */
class ModuleUpdater extends Updater implements DrupalUpdaterInterface {

  public function getInstallDirectory() {
    // If the module is installed in the configuration path (conf_path())
    // we should install it there.  If it is located elsewhere, such as
    // sites/all/modules we should install it in the conf_path/modules directory.
    if ($this->isInstalled()) {
      $installed_path = drupal_get_path('module', $this->name);
      if (substr($installed_path, 0, strlen(conf_path())) === conf_path()) {
        return dirname(DRUPAL_ROOT . '/' . $installed_path);
      }
    }
    return DRUPAL_ROOT . '/' . conf_path() . '/modules';
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
    require_once './includes/install.inc';
    require_once './includes/update.inc';

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
      l(t('Enable newly added modules in !project', array('!project' => $this->title)), 'admin/config/modules'),
    );
  }

  public function postUpdateTasks() {
    // @todo: If there are schema updates.
    return array(
      l(t('Run database updates for !project', array('!project' => $this->title)), 'update.php'),
    );
  }

}

/**
 * Class for updating themes using FileTransfer classes via authorize.php.
 */
class ThemeUpdater extends Updater implements DrupalUpdaterInterface {

  public function getInstallDirectory() {
    // If the theme is installed in the configuration path (conf_path())
    // we should install it there.  If it is located elsewhere, such as
    // sites/all/themes we should install it in the conf_path/themes directory.
    if ($this->isInstalled()) {
      $installed_path = drupal_get_path('theme', $this->name);
      if (substr($installed_path, 0, strlen(conf_path())) === conf_path()) {
        return dirname(DRUPAL_ROOT . '/' . $installed_path);
      }
    }
    return DRUPAL_ROOT . '/' . conf_path() . '/themes';
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
    drupal_static_reset('_system_get_theme_data');
    _system_rebuild_theme_data();

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
