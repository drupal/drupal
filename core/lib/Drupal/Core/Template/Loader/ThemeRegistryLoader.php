<?php

namespace Drupal\Core\Template\Loader;

use Drupal\Core\Theme\Registry;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * Loads templates based on information from the Drupal theme registry.
 *
 * Allows for template inheritance based on the currently active template.
 */
class ThemeRegistryLoader extends FilesystemLoader {

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
   * @return string|false
   *   The path to the template, or false if the template is not found.
   *
   * @throws \Twig\Error\LoaderError
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
      throw new LoaderError(sprintf('Unable to find template "%s" in the Drupal theme registry.', $name));
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey($name) {
    // The parent implementation does unnecessary work that triggers
    // deprecations in PHP 8.1.
    return $this->findTemplate($name) ?: '';
  }

}
