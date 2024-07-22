<?php

namespace Drupal\Core\Updater;

use Drupal\Core\Url;

/**
 * Defines a class for updating modules.
 *
 * Uses Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class Module extends Updater implements UpdaterInterface {

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
   */
  public static function canUpdate($project_name) {
    return (bool) \Drupal::service('extension.list.module')->getPath($project_name);
  }

  /**
   * {@inheritdoc}
   */
  public function postInstallTasks() {
    // Since this is being called outside of the primary front controller,
    // the base_url needs to be set explicitly to ensure that links are
    // relative to the site root.
    // @todo Simplify with https://www.drupal.org/node/2548095
    $default_options = [
      '#type' => 'link',
      '#options' => [
        'absolute' => TRUE,
        'base_url' => $GLOBALS['base_url'],
      ],
    ];
    return [
      $default_options + [
        '#url' => Url::fromRoute('system.modules_list'),
        '#title' => t('Install newly added modules'),
      ],
      $default_options + [
        '#url' => Url::fromRoute('system.admin'),
        '#title' => t('Administration pages'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }

}
