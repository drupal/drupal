<?php

declare(strict_types=1);

namespace Drupal\workflow_type_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\workflow_type_test\Plugin\WorkflowType\WorkflowCustomAccessType;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for workflow_type_test.
 */
class WorkflowTypeTestHooks {

  /**
   * Implements hook_workflow_type_info_alter().
   */
  #[Hook('workflow_type_info_alter')]
  public function workflowTypeInfoAlter(&$definitions): void {
    // Allow tests to override the workflow type definitions.
    $state = \Drupal::state();
    if ($state->get('workflow_type_test.plugin_definitions') !== NULL) {
      $definitions = $state->get('workflow_type_test.plugin_definitions');
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access() for the Workflow entity type.
   */
  #[Hook('workflow_access')]
  public function workflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account) {
    if ($entity->getTypePlugin()->getPluginId() === 'workflow_custom_access_type') {
      return WorkflowCustomAccessType::workflowAccess($entity, $operation, $account);
    }
    return AccessResult::neutral();
  }

}
