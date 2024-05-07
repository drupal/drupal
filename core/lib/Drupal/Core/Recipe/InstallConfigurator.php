<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Extension\Dependency;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;

/**
 * @internal
 *   This API is experimental.
 */
final class InstallConfigurator {

  /**
   * The list of modules to install.
   *
   * This list is sorted an includes any module dependencies of the provided
   * extensions.
   *
   * @var string[]
   */
  public readonly array $modules;

  /**
   * The list of themes to install.
   *
   * This list is sorted an includes any theme dependencies of the provided
   * extensions.
   *
   * @var string[]
   */
  public readonly array $themes;

  /**
   * @param string[] $extensions
   *   A list of extensions for a recipe to install.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme list service.
   */
  public function __construct(array $extensions, ModuleExtensionList $module_list, ThemeExtensionList $theme_list) {
    assert(Inspector::assertAllStrings($extensions), 'Extension names must be strings.');
    $extensions = array_map(fn($extension) => Dependency::createFromString($extension)->getName(), $extensions);
    $extensions = array_combine($extensions, $extensions);
    $module_data = $module_list->reset()->getList();
    $theme_data = $theme_list->reset()->getList();

    $modules = array_intersect_key($extensions, $module_data);
    $themes = array_intersect_key($extensions, $theme_data);

    $missing_extensions = array_diff($extensions, $modules, $themes);

    // Add theme module dependencies.
    foreach ($themes as $theme => $value) {
      $modules = array_merge($modules, array_keys($theme_data[$theme]->module_dependencies));
    }

    // Add modules that other modules depend on.
    foreach ($modules as $module) {
      if ($module_data[$module]->requires) {
        $modules = array_merge($modules, array_keys($module_data[$module]->requires));
      }
    }

    // Remove all modules that have been installed already.
    $modules = array_diff(array_unique($modules), array_keys($module_list->getAllInstalledInfo()));
    $modules = array_combine($modules, $modules);

    // Create a sortable list of modules.
    foreach ($modules as $name => $value) {
      if (isset($module_data[$name])) {
        $modules[$name] = $module_data[$name]->sort;
      }
      else {
        $missing_extensions[$name] = $name;
      }
    }

    // Add any missing base themes to the list of themes to install.
    foreach ($themes as $theme => $value) {
      // $theme_data[$theme]->requires contains both theme and module
      // dependencies keyed by the extension machine names.
      // $theme_data[$theme]->module_dependencies contains only the module
      // dependencies keyed by the module extension machine name. Therefore,
      // we can find the theme dependencies by finding array keys for
      // 'requires' that are not in $module_dependencies.
      $theme_dependencies = array_diff_key($theme_data[$theme]->requires, $theme_data[$theme]->module_dependencies);
      $themes = array_merge($themes, array_keys($theme_dependencies));
    }

    // Remove all themes that have been installed already.
    $themes = array_diff(array_unique($themes), array_keys($theme_list->getAllInstalledInfo()));
    $themes = array_combine($themes, $themes);

    // Create a sortable list of themes.
    foreach ($themes as $name => $value) {
      if (isset($theme_data[$name])) {
        $themes[$name] = $theme_data[$name]->sort;
      }
      else {
        $missing_extensions[$name] = $name;
      }
    }

    if (!empty($missing_extensions)) {
      throw new RecipeMissingExtensionsException(array_values($missing_extensions));
    }

    arsort($modules);
    arsort($themes);
    $this->modules = array_keys($modules);
    $this->themes = array_keys($themes);
  }

}
