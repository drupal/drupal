<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Component\Utility\NestedArray;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageDefinition $definition
   *   The definition of the section storage.
   * @param string $path
   *   The path patten for the routes.
   * @param array $defaults
   *   (optional) An array of default parameter values.
   * @param array $requirements
   *   (optional) An array of requirements for parameters.
   * @param array $options
   *   (optional) An array of options.
   * @param string $route_name_prefix
   *   (optional) The prefix to use for the route name.
   */
  protected function buildLayoutRoutes(RouteCollection $collection, SectionStorageDefinition $definition, $path, array $defaults = [], array $requirements = [], array $options = [], $route_name_prefix = '') {
    $type = $definition->id();
    $defaults['section_storage_type'] = $type;
    // Provide an empty value to allow the section storage to be upcast.
    $defaults['section_storage'] = '';
    // Trigger the layout builder access check.
    $requirements['_has_layout_section'] = 'true';
    // Trigger the layout builder RouteEnhancer.
    $options['_layout_builder'] = TRUE;
    // Trigger the layout builder param converter.
    $parameters['section_storage']['layout_builder_tempstore'] = TRUE;
    // Merge the passed in options in after Layout Builder's parameters.
    $options = NestedArray::mergeDeep(['parameters' => $parameters], $options);

    if ($route_name_prefix) {
      $route_name_prefix = "layout_builder.$type.$route_name_prefix";
    }
    else {
      $route_name_prefix = "layout_builder.$type";
    }

    $main_defaults = $defaults;
    $main_defaults['is_rebuilding'] = FALSE;
    $main_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::layout';
    $main_defaults['_title_callback'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::title';
    $route = (new Route($path))
      ->setDefaults($main_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $collection->add("$route_name_prefix.view", $route);

    $save_defaults = $defaults;
    $save_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout';
    $route = (new Route("$path/save"))
      ->setDefaults($save_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $collection->add("$route_name_prefix.save", $route);

    $cancel_defaults = $defaults;
    $cancel_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout';
    $route = (new Route("$path/cancel"))
      ->setDefaults($cancel_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $collection->add("$route_name_prefix.cancel", $route);

    if (is_subclass_of($definition->getClass(), OverridesSectionStorageInterface::class)) {
      $revert_defaults = $defaults;
      $revert_defaults['_form'] = '\Drupal\layout_builder\Form\RevertOverridesForm';
      $route = (new Route("$path/revert"))
        ->setDefaults($revert_defaults)
        ->setRequirements($requirements)
        ->setOptions($options);
      $collection->add("$route_name_prefix.revert", $route);
    }
  }

}
