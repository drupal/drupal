<?php

namespace Drupal\Core\Template\Loader;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

/**
 * Loads templates from the filesystem.
 *
 * This loader adds module and theme template paths as namespaces to the Twig
 * filesystem loader so that templates can be referenced by namespace, like
 * "@block/block.html.twig" or "@my_theme/page.html.twig".
 */
class FilesystemLoader extends TwigFilesystemLoader {

  /**
   * Allowed file extensions.
   *
   * @var string[]
   */
  protected $allowedFileExtensions = ['css', 'html', 'js', 'svg', 'twig'];

  /**
   * Constructs a new FilesystemLoader object.
   *
   * @param string|array $paths
   *   A path or an array of paths to check for templates.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   * @param mixed[] $twig_config
   *   Twig configuration from the service container.
   */
  public function __construct($paths, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, array $twig_config = []) {
    parent::__construct($paths);

    // Add namespaced paths for modules and themes.
    $namespaces = [];
    foreach ($module_handler->getModuleList() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }
    foreach ($theme_handler->listInfo() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }

    foreach ($namespaces as $name => $path) {
      $this->addPath($path . '/templates', $name);
      // Allow accessing the root of an extension by using the namespace without
      // using directory traversal from the `/templates` directory.
      $this->addPath($path, $name);
    }
    if (!empty($twig_config['allowed_file_extensions'])) {
      // Provide a safe fallback for sites that have not updated their
      // services.yml file or rebuilt the container, as well as for child
      // classes.
      $this->allowedFileExtensions = $twig_config['allowed_file_extensions'];
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
  public function addPath(string $path, string $namespace = self::MAIN_NAMESPACE): void {
    // Invalidate the cache.
    $this->cache = [];
    $this->paths[$namespace][] = rtrim($path, '/\\');
  }

  /**
   * {@inheritdoc}
   */
  protected function findTemplate($name, $throw = TRUE) {
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    if (!in_array($extension, $this->allowedFileExtensions, TRUE)) {
      if (!$throw) {
        return NULL;
      }
      // Customize the list of extensions if no file extension is allowed.
      $extensions = $this->allowedFileExtensions;
      $no_extension = array_search('', $extensions, TRUE);
      if (is_int($no_extension)) {
        unset($extensions[$no_extension]);
        $extensions[] = 'or no file extension';
      }
      if (empty($extension)) {
        $extension = 'no file extension';
      }
      throw new LoaderError(sprintf("Template %s has an invalid file extension (%s). Only templates ending in one of %s are allowed. Set the twig.config.allowed_file_extensions container parameter to customize the allowed file extensions", $name, $extension, implode(', ', $extensions)));
    }

    // Previously it was possible to access files in the parent directory of a
    // namespace. This was removed in Twig 2.15.3. In order to support backwards
    // compatibility, we are adding path directory as a namespace, and therefore
    // we can remove the directory traversal from the name.
    // @todo deprecate this functionality for removal in Drupal 11.
    if (preg_match('/(^\@[^\/]+\/)\.\.\/(.*)/', $name, $matches)) {
      $name = $matches[1] . $matches[2];
    }

    return parent::findTemplate($name, $throw);
  }

}
