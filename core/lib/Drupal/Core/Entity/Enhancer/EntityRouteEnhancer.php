<?php

namespace Drupal\Core\Entity\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class EntityRouteEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->applies($route)) {
      return $defaults;
    }

    if (empty($defaults['_controller'])) {
      if (!empty($defaults['_entity_form'])) {
        $defaults = $this->enhanceEntityForm($defaults, $request);
      }
      elseif (!empty($defaults['_entity_list'])) {
        $defaults = $this->enhanceEntityList($defaults, $request);
      }
      elseif (!empty($defaults['_entity_view'])) {
        $defaults = $this->enhanceEntityView($defaults, $request);
      }
    }
    return $defaults;
  }

  /**
   * Returns whether the enhancer runs on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   *
   * @return bool
   */
  protected function applies(Route $route) {
    return !$route->hasDefault('_controller') &&
      ($route->hasDefault('_entity_form')
        || $route->hasDefault('_entity_list')
        || $route->hasDefault('_entity_view')
      );
  }

  /**
   * Update defaults for entity forms.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   */
  protected function enhanceEntityForm(array $defaults, Request $request) {
    $defaults['_controller'] = 'controller.entity_form:getContentResult';

    return $defaults;
  }

  /**
   * Update defaults for an entity list.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   */
  protected function enhanceEntityList(array $defaults, Request $request) {
    $defaults['_controller'] = '\Drupal\Core\Entity\Controller\EntityListController::listing';
    $defaults['entity_type'] = $defaults['_entity_list'];
    unset($defaults['_entity_list']);

    return $defaults;
  }

  /**
   * Update defaults for an entity view.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   *
   * @throws \RuntimeException
   *   Thrown when an entity of a type cannot be found in a route.
   */
  protected function enhanceEntityView(array $defaults, Request $request) {
    $defaults['_controller'] = '\Drupal\Core\Entity\Controller\EntityViewController::view';
    if (str_contains($defaults['_entity_view'], '.')) {
      // The _entity_view entry is of the form entity_type.view_mode.
      [$entity_type, $view_mode] = explode('.', $defaults['_entity_view']);
      $defaults['view_mode'] = $view_mode;
    }
    else {
      // Only the entity type is nominated, the view mode will use the
      // default.
      $entity_type = $defaults['_entity_view'];
    }
    // Set by reference so that we get the upcast value.
    if (!empty($defaults[$entity_type])) {
      $defaults['_entity'] = &$defaults[$entity_type];
    }
    else {
      // The entity is not keyed by its entity_type. Attempt to find it
      // using a converter.
      $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
      if ($route && is_object($route)) {
        $options = $route->getOptions();
        if (isset($options['parameters'])) {
          foreach ($options['parameters'] as $name => $details) {
            if (!empty($details['type'])) {
              $type = $details['type'];
              // Type is of the form entity:{entity_type}.
              $parameter_entity_type = substr($type, strlen('entity:'));
              if ($entity_type == $parameter_entity_type) {
                // We have the matching entity type. Set the '_entity' key
                // to point to this named placeholder. The entity in this
                // position is the one being rendered.
                $defaults['_entity'] = &$defaults[$name];
              }
            }
          }
        }
        else {
          throw new \RuntimeException(sprintf('Failed to find entity of type %s in route named %s', $entity_type, $defaults[RouteObjectInterface::ROUTE_NAME]));
        }
      }
      else {
        throw new \RuntimeException(sprintf('Failed to find entity of type %s in route named %s', $entity_type, $defaults[RouteObjectInterface::ROUTE_NAME]));
      }
    }
    unset($defaults['_entity_view']);

    return $defaults;
  }

}
