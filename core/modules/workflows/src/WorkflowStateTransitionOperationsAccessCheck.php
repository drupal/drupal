<?php

namespace Drupal\workflows;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access check for state and transition operations.
 */
class WorkflowStateTransitionOperationsAccessCheck implements AccessInterface {

  /**
   * Checks access for operations of workflow states and transitions.
   *
   * The value of '_workflow_access' is used to check to kind of access that
   * should be applied to a route in the context of a workflow and a state or
   * transition. States and transitions can individually have access control
   * applied to them for 'add', 'update' and 'delete'. By default workflows will
   * use the admin permission 'administer workflows' for all of these
   * operations, except for delete-state which checks there is at least one
   * state, a state does not have data and it's not a required state.
   *
   * For the update and delete operations, a workflow and a state or transition
   * is required in the route for the access check to be applied. For the "add"
   * operation, only a workflow is required. The '_workflow_access' requirement
   * translates into access checks on the workflow entity type in the formats:
   *   - "$operation-state:$state_id"
   *   - "$operation-transition:$transition_id"
   *
   * For example the following route definition with the path
   * "/test-workflow/foo-state/delete" the 'delete-state:foo-state' operation
   * will be checked:
   * @code
   * path: '/{workflow}/{workflow_state}/delete'
   * requirements:
   *   _workflow_access: 'delete-state'
   * @endcode
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   *
   * @throws \Exception
   *   Throws an exception when a route is defined with an invalid operation.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $workflow_operation = $this->getOperation($route_match);
    if (!preg_match('/^(?<operation>add|update|delete)-(?<type>state|transition)$/', $workflow_operation, $matches)) {
      throw new \Exception("Invalid _workflow_access operation '$workflow_operation' specified for route '{$route_match->getRouteName()}'.");
    }

    $parameters = $route_match->getParameters();
    $workflow = $parameters->get('workflow');
    if ($workflow && $matches['operation'] === 'add') {
      return $workflow->access($workflow_operation, $account, TRUE);
    }
    if ($workflow && $type = $parameters->get(sprintf('workflow_%s', $matches['type']))) {
      return $workflow->access(sprintf('%s:%s', $workflow_operation, $type), $account, TRUE);
    }

    return AccessResult::neutral();
  }

  /**
   * Get the operation that will be used for the access check.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return string
   *   The access operation.
   */
  protected function getOperation(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getRequirement('_workflow_access');
  }

}
