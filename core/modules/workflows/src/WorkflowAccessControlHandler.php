<?php

namespace Drupal\workflows;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Moderation State entity.
 *
 * @see \Drupal\workflows\Entity\Workflow.
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
class WorkflowAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The workflow type plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $workflowTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.workflows.type')
    );
  }

  /**
   * Constructs the workflow access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $workflow_type_manager
   *   The workflow type plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, PluginManagerInterface $workflow_type_manager) {
    parent::__construct($entity_type);
    $this->workflowTypeManager = $workflow_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\workflows\Entity\Workflow $entity */
    $workflow_type = $entity->getTypePlugin();
    if (strpos($operation, 'delete-state') === 0) {
      list(, $state_id) = explode(':', $operation, 2);
      // Deleting a state is editing a workflow, but also we should forbid
      // access if there is only one state.
      $admin_access = AccessResult::allowedIf(count($entity->getStates()) > 1)
        ->andIf(parent::checkAccess($entity, 'edit', $account))
        ->andIf(AccessResult::allowedIf(!in_array($state_id, $workflow_type->getRequiredStates(), TRUE)))
        ->addCacheableDependency($entity);
    }
    else {
      $admin_access = parent::checkAccess($entity, $operation, $account);
    }
    return $workflow_type->checkWorkflowAccess($entity, $operation, $account)->orIf($admin_access);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $workflow_types_count = count($this->workflowTypeManager->getDefinitions());
    $admin_access = parent::checkCreateAccess($account, $context, $entity_bundle);
    // Allow access if there is at least one workflow type. Since workflow types
    // are provided by modules this is cacheable until extensions change.
    return $admin_access->andIf(AccessResult::allowedIf($workflow_types_count > 0))->addCacheTags(['config:core.extension']);
  }

}
