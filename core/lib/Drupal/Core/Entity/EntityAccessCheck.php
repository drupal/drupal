<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a generic access checker for entities.
 */
class EntityAccessCheck implements AccessInterface {

  /**
   * Checks access to the entity operation on the given route.
   *
   * The route's '_entity_access' requirement must follow the pattern
   * 'slug.operation'. Typically, the slug is an entity type ID, but it can be
   * any slug defined in the route. The route match parameter corresponding to
   * the slug is checked to see if it is entity-like, that is: implements
   * EntityInterface. Available operations are: 'view', 'update', 'create', and
   * 'delete'.
   *
   * For example, this route configuration invokes a permissions check for
   * 'update' access to entities of type 'node':
   *
   * @code
   * example.route:
   *   path: '/foo/{node}/bar'
   *   requirements:
   *     _entity_access: 'node.update'
   * @endcode
   *
   * And this will check 'delete' access to a dynamic entity type:
   *
   * @code
   * example.route:
   *   path: '/foo/{entity_type}/{example}'
   *   requirements:
   *     _entity_access: 'example.delete'
   *   options:
   *     parameters:
   *       example:
   *         type: entity:{entity_type}
   * @endcode
   *
   * @see \Drupal\Core\ParamConverter\EntityConverter
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @link https://www.drupal.org/docs/8/api/routing-system/parameters-in-routes
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_entity_access');
    [$entity_type, $operation] = explode('.', $requirement);
    // If $entity_type parameter is a valid entity, call its own access check.
    $parameters = $route_match->getParameters();
    if ($parameters->has($entity_type)) {
      $entity = $parameters->get($entity_type);
      if ($entity instanceof EntityInterface) {
        return $entity->access($operation, $account, TRUE);
      }
    }
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return AccessResult::neutral();
  }

}
