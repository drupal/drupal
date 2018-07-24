<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the workspace manager.
 */
class WorkspaceManager implements WorkspaceManagerInterface {

  use StringTranslationTrait;

  /**
   * An array of entity type IDs that can not belong to a workspace.
   *
   * By default, only entity types which are revisionable and publishable can
   * belong to a workspace.
   *
   * @var string[]
   */
  protected $blacklist = [
    'workspace_association',
    'workspace',
  ];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The workspace negotiator service IDs.
   *
   * @var array
   */
  protected $negotiatorIds;

  /**
   * The current active workspace.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $activeWorkspace;

  /**
   * Constructs a new WorkspaceManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param array $negotiator_ids
   *   The workspace negotiator service IDs.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, StateInterface $state, LoggerInterface $logger, ClassResolverInterface $class_resolver, array $negotiator_ids) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->logger = $logger;
    $this->classResolver = $class_resolver;
    $this->negotiatorIds = $negotiator_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type) {
    if (!isset($this->blacklist[$entity_type->id()])
      && $entity_type->entityClassImplements(EntityPublishedInterface::class)
      && $entity_type->isRevisionable()) {
      return TRUE;
    }
    $this->blacklist[$entity_type->id()] = $entity_type->id();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->isEntityTypeSupported($entity_type)) {
        $entity_types[$entity_type_id] = $entity_type;
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace() {
    if (!isset($this->activeWorkspace)) {
      $request = $this->requestStack->getCurrentRequest();
      foreach ($this->negotiatorIds as $negotiator_id) {
        $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);
        if ($negotiator->applies($request)) {
          if ($this->activeWorkspace = $negotiator->getActiveWorkspace($request)) {
            break;
          }
        }
      }
    }

    // The default workspace negotiator always returns a valid workspace.
    return $this->activeWorkspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    // If the current user doesn't have access to view the workspace, they
    // shouldn't be allowed to switch to it.
    if (!$workspace->access('view') && !$workspace->isDefaultWorkspace()) {
      $this->logger->error('Denied access to view workspace %workspace_label for user %uid', [
        '%workspace_label' => $workspace->label(),
        '%uid' => $this->currentUser->id(),
      ]);
      throw new WorkspaceAccessException('The user does not have permission to view that workspace.');
    }

    $this->activeWorkspace = $workspace;

    // Set the workspace on the proper negotiator.
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->negotiatorIds as $negotiator_id) {
      $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);
      if ($negotiator->applies($request)) {
        $negotiator->setActiveWorkspace($workspace);
        break;
      }
    }

    $supported_entity_types = $this->getSupportedEntityTypes();
    foreach ($supported_entity_types as $supported_entity_type) {
      $this->entityTypeManager->getStorage($supported_entity_type->id())->resetCache();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldAlterOperations(EntityTypeInterface $entity_type) {
    return $this->isEntityTypeSupported($entity_type) && !$this->getActiveWorkspace()->isDefaultWorkspace();
  }

  /**
   * {@inheritdoc}
   */
  public function purgeDeletedWorkspacesBatch() {
    $deleted_workspace_ids = $this->state->get('workspace.deleted', []);

    // Bail out early if there are no workspaces to purge.
    if (empty($deleted_workspace_ids)) {
      return;
    }

    $batch_size = Settings::get('entity_update_batch_size', 50);

    /** @var \Drupal\workspaces\WorkspaceAssociationStorageInterface $workspace_association_storage */
    $workspace_association_storage = $this->entityTypeManager->getStorage('workspace_association');

    // Get the first deleted workspace from the list and delete the revisions
    // associated with it, along with the workspace_association entries.
    $workspace_id = reset($deleted_workspace_ids);
    $workspace_association_ids = $this->getWorkspaceAssociationRevisionsToPurge($workspace_id, $batch_size);

    if ($workspace_association_ids) {
      $workspace_associations = $workspace_association_storage->loadMultipleRevisions(array_keys($workspace_association_ids));
      foreach ($workspace_associations as $workspace_association) {
        $associated_entity_storage = $this->entityTypeManager->getStorage($workspace_association->target_entity_type_id->value);
        // Delete the associated entity revision.
        if ($entity = $associated_entity_storage->loadRevision($workspace_association->target_entity_revision_id->value)) {
          if ($entity->isDefaultRevision()) {
            $entity->delete();
          }
          else {
            $associated_entity_storage->deleteRevision($workspace_association->target_entity_revision_id->value);
          }
        }

        // Delete the workspace_association revision.
        if ($workspace_association->isDefaultRevision()) {
          $workspace_association->delete();
        }
        else {
          $workspace_association_storage->deleteRevision($workspace_association->getRevisionId());
        }
      }
    }

    // The purging operation above might have taken a long time, so we need to
    // request a fresh list of workspace association IDs. If it is empty, we can
    // go ahead and remove the deleted workspace ID entry from state.
    if (!$this->getWorkspaceAssociationRevisionsToPurge($workspace_id, $batch_size)) {
      unset($deleted_workspace_ids[$workspace_id]);
      $this->state->set('workspace.deleted', $deleted_workspace_ids);
    }
  }

  /**
   * Gets a list of workspace association IDs to purge.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param int $batch_size
   *   The maximum number of records that will be purged.
   *
   * @return array
   *   An array of workspace association IDs, keyed by their revision IDs.
   */
  protected function getWorkspaceAssociationRevisionsToPurge($workspace_id, $batch_size) {
    return $this->entityTypeManager->getStorage('workspace_association')
      ->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('workspace', $workspace_id)
      ->sort('revision_id', 'ASC')
      ->range(0, $batch_size)
      ->execute();
  }

}
