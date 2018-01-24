<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Overrides the module extension list to have a static cache.
 */
class InstallerModuleExtensionList extends ModuleExtensionList {

  /**
   * Static version of the added file names during the installer.
   *
   * @var string[]
   *
   * @internal
   */
  protected static $staticAddedPathNames;

  /**
   * {@inheritdoc}
   */
  public function setPathname($extension_name, $pathname) {
    parent::setPathname($extension_name, $pathname);

    // In the early installer the container is rebuilt multiple times. Therefore
    // we have to keep the added filenames across those rebuilds. This is not a
    // final design, but rather just a workaround resolved at some point,
    // hopefully.
    // @todo Remove as part of https://drupal.org/project/drupal/issues/2934063
    static::$staticAddedPathNames[$extension_name] = $pathname;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathname($extension_name) {
    if (isset($this->addedPathNames[$extension_name])) {
      return $this->addedPathNames[$extension_name];
    }
    elseif (isset($this->pathNames[$extension_name])) {
      return $this->pathNames[$extension_name];
    }
    elseif (isset(static::$staticAddedPathNames[$extension_name])) {
      return static::$staticAddedPathNames[$extension_name];
    }
    elseif (($path_names = $this->getPathnames()) && isset($path_names[$extension_name])) {
      // Ensure we don't have to do path scanning more than really needed.
      foreach ($path_names as $extension => $path_name) {
        static::$staticAddedPathNames[$extension] = $path_name;
      }
      return $path_names[$extension_name];
    }
    throw new \InvalidArgumentException("The {$this->type} $extension_name does not exist.");
  }

}
