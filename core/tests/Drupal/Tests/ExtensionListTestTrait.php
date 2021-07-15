<?php

namespace Drupal\Tests;

/**
 * Provides extension list methods.
 */
trait ExtensionListTestTrait {

  /**
   * Gets the path for the specified module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return string
   *   The Drupal-root relative path to the module directory.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the module does not exist.
   */
  protected function getModulePath(string $module_name): string {
    return \Drupal::service('extension.list.module')->getPath($module_name);
  }

  /**
   * Gets the path for the specified theme.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return string
   *   The Drupal-root relative path to the theme directory.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the theme does not exist.
   */
  protected function getThemePath(string $theme_name): string {
    return \Drupal::service('extension.list.theme')->getPath($theme_name);
  }

}
