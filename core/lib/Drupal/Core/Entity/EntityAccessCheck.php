<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessCheck.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a generic access checker for entities.
 */
class EntityAccessCheck implements AccessInterface {

  /**
   * Checks access to the entity operation on the given route.
   *
   * The value of the '_entity_access' key must be in the pattern
   * 'entity_type.operation.' The entity type must match the {entity_type}
   * parameter in the route pattern. This will check a node for 'update' access:
   * @code
   * pattern: '/foo/{node}/bar'
   * requirements:
   *   _entity_access: 'node.update'
   * @endcode
   * Available operations are 'view', 'update', 'create', and 'delete'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_entity_access');
    list($entity_type, $operation) = explode('.', $requirement);
    // If there is valid entity of the given entity type, check its access.
    if ($request->attributes->has($entity_type)) {
      $entity = $request->attributes->get($entity_type);
      if ($entity instanceof EntityInterface) {
        return $entity->access($operation, $account) ? static::ALLOW : static::DENY;
      }
    }
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return static::DENY;
  }

}
