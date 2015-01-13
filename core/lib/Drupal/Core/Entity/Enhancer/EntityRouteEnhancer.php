<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
 */

namespace Drupal\Core\Entity\Enhancer;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class EntityRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_controller'])) {
      if (!empty($defaults['_entity_form'])) {
        $defaults['_controller'] = 'controller.entity_form:getContentResult';
      }
      elseif (!empty($defaults['_entity_list'])) {
        $defaults['_controller'] = '\Drupal\Core\Entity\Controller\EntityListController::listing';
        $defaults['entity_type'] = $defaults['_entity_list'];
        unset($defaults['_entity_list']);
      }
      elseif (!empty($defaults['_entity_view'])) {
        $defaults['_controller'] = '\Drupal\Core\Entity\Controller\EntityViewController::view';
        if (strpos($defaults['_entity_view'], '.') !== FALSE) {
          // The _entity_view entry is of the form entity_type.view_mode.
          list($entity_type, $view_mode) = explode('.', $defaults['_entity_view']);
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
      }
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return !$route->hasDefault('_controller') &&
      ($route->hasDefault('_entity_form')
        || $route->hasDefault('_entity_list')
        || $route->hasDefault('_entity_view')
      );
  }

}
