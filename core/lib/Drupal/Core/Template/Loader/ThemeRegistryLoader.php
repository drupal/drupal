<?php

/**
 * @file
 * Contains \Drupal\Core\Template\Loader\ThemeRegistryLoader.
 */

namespace Drupal\Core\Template\Loader;

use Drupal\Core\Theme\Registry;

/**
 * Loads templates based on information from the Drupal theme registry.
 *
 * Allows for template inheritance based on the currently active template.
 */
class ThemeRegistryLoader extends \Twig_Loader_Filesystem {

  /**
   * The theme registry used to determine which template to use.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Constructs a new ThemeRegistryLoader object.
   *
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   */
  public function __construct(Registry $theme_registry) {
    $this->themeRegistry = $theme_registry;
  }

  /**
   * Finds the path to the requested template.
   *
   * @param string $name
   *   The name of the template to load.
   * @param bool $throw
   *   Whether to throw an exception when an error occurs.
   *
   * @return string
   *   The path to the template.
   *
   * @throws \Twig_Error_Loader
   *   Thrown if a template matching $name cannot be found.
   */
  protected function findTemplate($name, $throw = TRUE) {
    // Allow for loading based on the Drupal theme registry.
    $hook = str_replace('.html.twig', '', strtr($name, '-', '_'));
    $theme_registry = $this->themeRegistry->getRuntime();

    if ($theme_registry->has($hook)) {
      $info = $theme_registry->get($hook);
      if (isset($info['path'])) {
        $path = $info['path'] . '/' . $name;
      }
      elseif (isset($info['template'])) {
        $path = $info['template'] . '.html.twig';
      }
      if (isset($path) && is_file($path)) {
        return $this->cache[$name] = $path;
      }
    }

    if ($throw) {
      throw new \Twig_Error_Loader(sprintf('Unable to find template "%s" in the Drupal theme registry.', $name));
    }
  }

}
