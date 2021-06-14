<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\WorkflowInterface;

/**
 * A test workflow with custom state/transition access rules applied.
 *
 * @WorkflowType(
 *   id = "workflow_custom_access_type",
 *   label = @Translation("Workflow Custom Access Type Test"),
 * )
 */
class WorkflowCustomAccessType extends WorkflowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [
        'cannot_update' => [
          'label' => 'Cannot Update State',
          'weight' => 0,
        ],
        'cannot_delete' => [
          'label' => 'Cannot Delete State',
          'weight' => 0,
        ],
      ],
      'transitions' => [
        'cannot_update' => [
          'label' => 'Cannot Update Transition',
          'to' => 'cannot_update',
          'weight' => 0,
          'from' => [
            'cannot_update',
          ],
        ],
        'cannot_delete' => [
          'label' => 'Cannot Delete Transition',
          'to' => 'cannot_delete',
          'weight' => 1,
          'from' => [
            'cannot_delete',
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   *
   * @see workflow_type_test_workflow_access
   */
  public static function workflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account) {
    $forbidden_operations = \Drupal::state()->get('workflow_type_test_forbidden_operations', []);
    return in_array($operation, $forbidden_operations, TRUE)
      ? AccessResult::forbidden()
      : AccessResult::neutral();
  }

}
