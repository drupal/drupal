<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
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
   * The current active workspace or FALSE if there is no active workspace.
   *
   * @var \Drupal\workspaces\WorkspaceInterface|false
   */
  protected $activeWorkspace;

  public function __construct(protected RequestStack $requestStack, protected EntityTypeManagerInterface $entityTypeManager, protected MemoryCacheInterface $entityMemoryCache, protected AccountProxyInterface $currentUser, protected StateInterface $state, protected LoggerInterface $logger, protected ClassResolverInterface $classResolver, protected WorkspaceAssociationInterface $workspaceAssociation, protected WorkspaceInformationInterface $workspaceInfo, protected array $negotiatorIds = []) {
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
        /** @var \Drupal\workspaces\Negotiator\WorkspaceIdNegotiatorInterface $negotiator */
        $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);

        if ($negotiator->applies($request)) {
          if ($workspace_id = $negotiator->getActiveWorkspaceId($request)) {
            /** @var \Drupal\workspaces\WorkspaceInterface $negotiated_workspace */
            $negotiated_workspace = $this->entityTypeManager
              ->getStorage('workspace')
              ->load($workspace_id);
          }

          // By default, 'view' access is checked when a workspace is activated,
          // but it should also be checked when retrieving the currently active
          // workspace.
          if (isset($negotiated_workspace) && $negotiated_workspace->access('view')) {
            // Notify the negotiator that its workspace has been selected.
            $negotiator->setActiveWorkspace($negotiated_workspace);

            $active_workspace = $negotiated_workspace;
            break;
          }
        }
      }

      // If no negotiator was able to provide a valid workspace, default to the
      // live version of the site.
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
    $cache_tags_to_invalidate = [];
    foreach (array_keys($this->workspaceInfo->getSupportedEntityTypes()) as $entity_type_id) {
      $this->entityTypeManager->getStorage($entity_type_id)->resetCache();
      $cache_tags_to_invalidate[] = 'entity.memory_cache:' . $entity_type_id;
    }
    $this->entityMemoryCache->invalidateTags($cache_tags_to_invalidate);

    // Clear the static cache for path aliases. We can't inject the path alias
    // manager service because it would create a circular dependency.
    if (\Drupal::hasService('path_alias.manager')) {
      \Drupal::service('path_alias.manager')->cacheClear();
    }
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

    $all_associated_revisions = [];
    foreach (array_keys($this->workspaceInfo->getSupportedEntityTypes()) as $entity_type_id) {
      $all_associated_revisions[$entity_type_id] = $this->workspaceAssociation->getAssociatedRevisions($workspace_id, $entity_type_id);
    }
    $all_associated_revisions = array_filter($all_associated_revisions);

    $count = 1;
    foreach ($all_associated_revisions as $entity_type_id => $associated_revisions) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $associated_entity_storage */
      $associated_entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

      // Sort the associated revisions in reverse ID order, so we can delete the
      // most recent revisions first.
      krsort($associated_revisions);

      // Get a list of default revisions tracked by the given workspace, because
      // they need to be handled differently than pending revisions.
      $initial_revision_ids = $this->workspaceAssociation->getAssociatedInitialRevisions($workspace_id, $entity_type_id);

      foreach (array_keys($associated_revisions) as $revision_id) {
        if ($count > $batch_size) {
          continue 2;
        }

        // If the workspace is tracking the entity's default revision (i.e. the
        // entity was created inside that workspace), we need to delete the
        // whole entity after all of its pending revisions are gone.
        if (isset($initial_revision_ids[$revision_id])) {
          $associated_entity_storage->delete([$associated_entity_storage->load($initial_revision_ids[$revision_id])]);
        }
        else {
          // Delete the associated entity revision.
          $associated_entity_storage->deleteRevision($revision_id);
        }
        $count++;
      }
    }

    // The purging operation above might have taken a long time, so we need to
    // request a fresh list of tracked entities. If it is empty, we can go ahead
    // and remove the deleted workspace ID entry from state.
    $has_associated_revisions = FALSE;
    foreach (array_keys($this->workspaceInfo->getSupportedEntityTypes()) as $entity_type_id) {
      if (!empty($this->workspaceAssociation->getAssociatedRevisions($workspace_id, $entity_type_id))) {
        $has_associated_revisions = TRUE;
        break;
      }
    }
    if (!$has_associated_revisions) {
      unset($deleted_workspace_ids[$workspace_id]);
      $this->state->set('workspace.deleted', $deleted_workspace_ids);

      // Delete any possible leftover association entries.
      $this->workspaceAssociation->deleteAssociations($workspace_id);
    }
  }

}
