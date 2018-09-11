<?php

namespace Drupal\workflows;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a access checker for deleting a workflow state.
 *
 * @internal
 *   Marked as internal for use by the workflows module only.
 *
 * @deprecated
 *   Using the _workflow_state_delete_access check is deprecated in Drupal 8.6.0
 *   and will be removed before Drupal 9.0.0, you can use _workflow_access in
 *   route definitions instead.
 *   @code
 *   # The old approach:
 *   requirements:
 *     _workflow_state_delete_access: 'true'
 *   # The new approach:
 *   requirements:
 *     _workflow_access: 'delete-state'
 *   @endcode
 *   As an internal API the ability to use _workflow_state_delete_access may
 *   also be removed in a minor release.
 *
 * @see \Drupal\workflows\WorkflowStateTransitionOperationsAccessCheck
 * @see https://www.drupal.org/node/2929327
 */
class WorkflowDeleteAccessCheck extends WorkflowStateTransitionOperationsAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    @trigger_error('Using the _workflow_state_delete_access check is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0, use _workflow_access instead. As an internal API _workflow_state_delete_access may also be removed in a minor release.', E_USER_DEPRECATED);
    return parent::access($route_match, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOperation(RouteMatchInterface $route_match) {
    return 'delete-state';
  }

}
