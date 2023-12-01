<?php

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
    if ($this->isInstalled() && ($relative_path = drupal_get_path('module', $this->name))) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'sites/all/modules';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  public function isInstalled() {
    return (bool) drupal_get_filename('module', $this->name, NULL, FALSE);
  }

  public static function canUpdateDirectory($directory) {
    if (file_scan_directory($directory, '/.*\.module$/')) {
      return TRUE;
    }
    return FALSE;
  }

  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('module', $project_name);
  }

  /**
   * Returns a list of post install actions.
   */
  public function postInstallTasks() {
    return array(
      l(t('Install another module'), 'admin/modules/install'),
      l(t('Enable newly added modules'), 'admin/modules'),
      l(t('Administration pages'), 'admin'),
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
    if ($this->isInstalled() && ($relative_path = drupal_get_path('theme', $this->name))) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'sites/all/themes';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  public function isInstalled() {
    return (bool) drupal_get_filename('theme', $this->name, NULL, FALSE);
  }

  static function canUpdateDirectory($directory) {
    // This is a lousy test, but don't know how else to confirm it is a theme.
    if (file_scan_directory($directory, '/.*\.module$/')) {
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

  }

  public function postInstallTasks() {
    return array(
      l(t('Enable newly added themes'), 'admin/appearance'),
      l(t('Administration pages'), 'admin'),
    );
  }
}
