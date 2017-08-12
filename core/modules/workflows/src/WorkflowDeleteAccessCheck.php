<?php

namespace Drupal\workflows;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a access checker for deleting a workflow state.
 *
 * @internal
 *   Marked as internal until it's validated this should form part of the public
 *   API in https://www.drupal.org/node/2897148.
 */
class WorkflowDeleteAccessCheck implements AccessInterface {

  /**
   * Checks access to deleting a workflow state for a particular route.
   *
   * The value of '_workflow_state_delete_access' is ignored. The route must
   * have the parameters 'workflow' and 'workflow_state'. For example:
   * @code
   * pattern: '/foo/{workflow}/bar/{workflow_state}/delete'
   * requirements:
   *   _workflow_state_delete_access: 'true'
   * @endcode
   * @see \Drupal\Core\ParamConverter\EntityConverter
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // If there is valid entity of the given entity type, check its access.
    $parameters = $route_match->getParameters();
    if ($parameters->has('workflow') && $parameters->has('workflow_state')) {
      $entity = $parameters->get('workflow');
      if ($entity instanceof EntityInterface) {
        return $entity->access('delete-state:' . $parameters->get('workflow_state'), $account, TRUE);
      }
    }
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return AccessResult::neutral();
  }

}
