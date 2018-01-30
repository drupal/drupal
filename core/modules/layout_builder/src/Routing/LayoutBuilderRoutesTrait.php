<?php

namespace Drupal\layout_builder\Routing;

use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a trait for building routes for a Layout Builder UI.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
trait LayoutBuilderRoutesTrait {

  /**
   * Builds the layout routes for the given values.
   *
   * @param string $class
   *   The class defining the section storage.
   * @param string $route_name_prefix
   *   The prefix to use for the route name.
   * @param string $path
   *   The path patten for the routes.
   * @param array $defaults
   *   An array of default parameter values.
   * @param array $requirements
   *   An array of requirements for parameters.
   * @param array $options
   *   An array of options.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  protected function buildRoute($class, $route_name_prefix, $path, array $defaults, array $requirements, array $options) {
    $routes = [];

    if (!is_subclass_of($class, SectionStorageInterface::class)) {
      return $routes;
    }

    $defaults['section_storage_type'] = $class::getStorageType();
    // Provide an empty value to allow the section storage to be upcast.
    $defaults['section_storage'] = '';
    // Trigger the layout builder access check.
    $requirements['_has_layout_section'] = 'true';
    // Trigger the layout builder RouteEnhancer.
    $options['_layout_builder'] = TRUE;

    $main_defaults = $defaults;
    $main_defaults['is_rebuilding'] = FALSE;
    $main_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::layout';
    $main_defaults['_title_callback'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::title';
    $route = (new Route($path))
      ->setDefaults($main_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder"] = $route;

    $save_defaults = $defaults;
    $save_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout';
    $route = (new Route("$path/save"))
      ->setDefaults($save_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder_save"] = $route;

    $cancel_defaults = $defaults;
    $cancel_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout';
    $route = (new Route("$path/cancel"))
      ->setDefaults($cancel_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder_cancel"] = $route;

    if (is_subclass_of($class, OverridesSectionStorageInterface::class)) {
      $revert_defaults = $defaults;
      $revert_defaults['_form'] = '\Drupal\layout_builder\Form\RevertOverridesForm';
      $route = (new Route("$path/revert"))
        ->setDefaults($revert_defaults)
        ->setRequirements($requirements)
        ->setOptions($options);
      $routes["{$route_name_prefix}.layout_builder_revert"] = $route;
    }

    return $routes;
  }

}
