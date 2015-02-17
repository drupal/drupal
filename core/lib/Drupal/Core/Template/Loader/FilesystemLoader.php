<?php

/**
 * @file
 * Contains \Drupal\Core\Template\Loader\FilesystemLoader.
 */

namespace Drupal\Core\Template\Loader;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Loads templates from the filesystem.
 *
 * This loader adds module and theme template paths as namespaces to the Twig
 * filesystem loader so that templates can be referenced by namespace, like
 * @block/block.html.twig or @mytheme/page.html.twig.
 */
class FilesystemLoader extends \Twig_Loader_Filesystem {

  /**
   * Constructs a new FilesystemLoader object.
   *
   * @param string|array $paths
   *   A path or an array of paths to check for templates.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct($paths = array(), ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct($paths);

    // Add namespaced paths for modules and themes.
    $namespaces = array();
    foreach ($module_handler->getModuleList() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }
    foreach ($theme_handler->listInfo() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }

    foreach ($namespaces as $name => $path) {
      $this->addPath($path . '/templates', $name);
    }
  }

  /**
   * Adds a path where templates are stored.
   *
   * @param string $path
   *   A path where to look for templates.
   * @param string $namespace
   *   (optional) A path name.
   */
  public function addPath($path, $namespace = self::MAIN_NAMESPACE) {
    // Invalidate the cache.
    $this->cache = array();
    $this->paths[$namespace][] = rtrim($path, '/\\');
  }

}
