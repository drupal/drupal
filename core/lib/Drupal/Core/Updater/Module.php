<?php

namespace Drupal\Core\Updater;

/**
 * Defines a class for updating modules.
 *
 * Uses Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
class Module extends Updater implements UpdaterInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($source, $root) {
    @trigger_error('The ' . __NAMESPACE__ . '\Module class is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);
    parent::__construct($source, $root);
  }

  /**
   * Returns the directory where a module should be installed.
   *
   * If the module is already installed, ModuleExtensionList::getPath() will
   * return a valid path and we should install it there. If we're installing a
   * new module, we always want it to go into /modules, since that's where all
   * the documentation recommends users install their modules, and there's no
   * way that can conflict on a multi-site installation, since the Update
   * manager won't let you install a new module if it's already found on your
   * system, and if there was a copy in the top-level we'd see it.
   *
   * @return string
   *   The absolute path of the directory.
   */
  public function getInstallDirectory() {
    if ($this->isInstalled() && ($relative_path = \Drupal::service('extension.list.module')->getPath($this->name))) {
      // The return value of ExtensionList::getPath() is always relative to the
      // site, so prepend DRUPAL_ROOT.
      return DRUPAL_ROOT . '/' . dirname($relative_path);
    }
    else {
      // When installing a new module, prepend the requested root directory.
      return $this->root . '/' . $this->getRootDirectoryRelativePath();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getRootDirectoryRelativePath() {
    return 'modules';
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    // Check if the module exists in the file system, regardless of whether it
    // is enabled or not.
    /** @var \Drupal\Core\Extension\ExtensionList $module_extension_list */
    $module_extension_list = \Drupal::service('extension.list.module');
    return $module_extension_list->exists($this->name);
  }

  /**
   * {@inheritdoc}
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
   *   TRUE if the the project can be updated, FALSE otherwise.
   */
  public static function canUpdate($project_name) {
    return (bool) \Drupal::service('extension.list.module')->getPath($project_name);
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }

}
