<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
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
   * An array of which entity types are supported.
   *
   * @var string[]
   */
  protected $supported = [
    'workspace' => FALSE,
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
   * The entity memory cache service.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $entityMemoryCache;

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
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The workspace negotiator service IDs.
   *
   * @var array
   */
  protected $negotiatorIds;

  /**
   * The current active workspace or FALSE if there is no active workspace.
   *
   * @var \Drupal\workspaces\WorkspaceInterface|false
   */
  protected $activeWorkspace;

  /**
   * Constructs a new WorkspaceManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $entity_memory_cache
   *   The entity memory cache service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   * @param array $negotiator_ids
   *   The workspace negotiator service IDs.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, MemoryCacheInterface $entity_memory_cache, AccountProxyInterface $current_user, StateInterface $state, LoggerInterface $logger, ClassResolverInterface $class_resolver, WorkspaceAssociationInterface $workspace_association, array $negotiator_ids) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityMemoryCache = $entity_memory_cache;
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->logger = $logger;
    $this->classResolver = $class_resolver;
    $this->workspaceAssociation = $workspace_association;
    $this->negotiatorIds = $negotiator_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    if (!isset($this->supported[$entity_type_id])) {
      // Only entity types which are revisionable and publishable can belong
      // to a workspace.
      $this->supported[$entity_type_id] = $entity_type->entityClassImplements(EntityPublishedInterface::class) && $entity_type->isRevisionable();
    }
    return $this->supported[$entity_type_id];
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
  public function hasActiveWorkspace() {
    return $this->getActiveWorkspace() !== FALSE;
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
          // By default, 'view' access is checked when a workspace is activated,
          // but it should also be checked when retrieving the currently active
          // workspace.
          if (($negotiated_workspace = $negotiator->getActiveWorkspace($request)) && $negotiated_workspace->access('view')) {
            $active_workspace = $negotiated_workspace;
            break;
          }
        }
      }

      // If no negotiator was able to determine the active workspace, default to
      // the live version of the site.
      $this->activeWorkspace = $active_workspace ?? FALSE;
    }

    return $this->activeWorkspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    $this->doSwitchWorkspace($workspace);

    // Set the workspace on the proper negotiator.
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->negotiatorIds as $negotiator_id) {
      $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);
      if ($negotiator->applies($request)) {
        $negotiator->setActiveWorkspace($workspace);
        break;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function switchToLive() {
    $this->doSwitchWorkspace(NULL);

    // Unset the active workspace on all negotiators.
    foreach ($this->negotiatorIds as $negotiator_id) {
      $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);
      $negotiator->unsetActiveWorkspace();
    }

    return $this;
  }

  /**
   * Switches the current workspace.
   *
   * @param \Drupal\workspaces\WorkspaceInterface|null $workspace
   *   The workspace to set as active or NULL to switch out of the currently
   *   active workspace.
   *
   * @throws \Drupal\workspaces\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  protected function doSwitchWorkspace($workspace) {
    // If the current user doesn't have access to view the workspace, they
    // shouldn't be allowed to switch to it, except in CLI processes.
    if ($workspace && PHP_SAPI !== 'cli' && !$workspace->access('view')) {
      $this->logger->error('Denied access to view workspace %workspace_label for user %uid', [
        '%workspace_label' => $workspace->label(),
        '%uid' => $this->currentUser->id(),
      ]);
      throw new WorkspaceAccessException('The user does not have permission to view that workspace.');
    }

    $this->activeWorkspace = $workspace ?: FALSE;

    // Clear the static entity cache for the supported entity types.
    $cache_tags_to_invalidate = array_map(function ($entity_type_id) {
      return 'entity.memory_cache:' . $entity_type_id;
    }, array_keys($this->getSupportedEntityTypes()));
    $this->entityMemoryCache->invalidateTags($cache_tags_to_invalidate);

    // Clear the static cache for path aliases. We can't inject the path alias
    // manager service because it would create a circular dependency.
    \Drupal::service('path_alias.manager')->cacheClear();
  }

  /**
   * {@inheritdoc}
   */
  public function executeInWorkspace($workspace_id, callable $function) {
    /** @var \Drupal\workspaces\WorkspaceInterface $workspace */
    $workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id);

    if (!$workspace) {
      throw new \InvalidArgumentException('The ' . $workspace_id . ' workspace does not exist.');
    }

    $previous_active_workspace = $this->getActiveWorkspace();
    $this->doSwitchWorkspace($workspace);
    $result = $function();
    $this->doSwitchWorkspace($previous_active_workspace);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function executeOutsideWorkspace(callable $function) {
    $previous_active_workspace = $this->getActiveWorkspace();
    $this->doSwitchWorkspace(NULL);
    $result = $function();
    $this->doSwitchWorkspace($previous_active_workspace);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldAlterOperations(EntityTypeInterface $entity_type) {
    return $this->isEntityTypeSupported($entity_type) && $this->hasActiveWorkspace();
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

    // Get the first deleted workspace from the list and delete the revisions
    // associated with it, along with the workspace association records.
    $workspace_id = reset($deleted_workspace_ids);
    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($workspace_id);

    $count = 1;
    foreach ($tracked_entities as $entity_type_id => $entities) {
      $associated_entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
      $associated_revisions = $this->workspaceAssociation->getAssociatedRevisions($workspace_id, $entity_type_id);
      foreach (array_keys($associated_revisions) as $revision_id) {
        if ($count > $batch_size) {
          continue 2;
        }

        // Delete the associated entity revision.
        $associated_entity_storage->deleteRevision($revision_id);
        $count++;
      }
      // Delete the workspace association entries.
      $this->workspaceAssociation->deleteAssociations($workspace_id, $entity_type_id, $entities);
    }

    // The purging operation above might have taken a long time, so we need to
    // request a fresh list of tracked entities. If it is empty, we can go ahead
    // and remove the deleted workspace ID entry from state.
    if (!$this->workspaceAssociation->getTrackedEntities($workspace_id)) {
      unset($deleted_workspace_ids[$workspace_id]);
      $this->state->set('workspace.deleted', $deleted_workspace_ids);
    }
  }

}
