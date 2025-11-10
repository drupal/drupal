<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\workspaces\WorkspaceTrackerInterface;

/**
 * Hook implementations for workspaces.
 */
class WorkspacesHooks {

  use StringTranslationTrait;

  public function __construct(
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceTrackerInterface $workspaceTracker,
    protected WorkspaceInformationInterface $workspaceInfo,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    $output = '';
    switch ($route_name) {
      // Main module help for the Workspaces module.
      case 'help.page.workspaces':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Workspaces module allows workspaces to be defined and switched between. Content is then assigned to the active workspace when created. For more information, see the <a href=":workspaces">online documentation for the Workspaces module</a>.', [':workspaces' => 'https://www.drupal.org/docs/8/core/modules/workspace/overview']) . '</p>';
        break;
    }
    return $output;
  }

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall(string $module): void {
    if ($module !== 'workspaces') {
      return;
    }

    foreach ($this->entityDefinitionUpdateManager->getEntityTypes() as $entity_type) {
      if ($this->workspaceInfo->isEntityTypeSupported($entity_type)) {
        $entity_type->setRevisionMetadataKey('workspace', 'workspace');
        $this->entityDefinitionUpdateManager->updateEntityType($entity_type);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'menu_link_content' entities.
   */
  #[Hook('menu_link_content_update')]
  public function menuLinkContentUpdate(EntityInterface $entity): void {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      // We are not updating the menu tree definitions when a custom menu link
      // entity is saved as a pending revision (because the parent can not be
      // changed), so we need to clear the system menu cache manually. However,
      // inserting or deleting a custom menu link updates the menu tree
      // definitions, so we don't have to do anything in those cases.
      $cache_tags = Cache::buildTags('config:system.menu', [$entity->getMenuName()], '.');
      $this->cacheTagsInvalidator->invalidateTags($cache_tags);
    }
  }

  /**
   * Implements hook_cron().
   *
   * @internal
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->workspaceManager->executeOutsideWorkspace(function () {
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
        $all_associated_revisions[$entity_type_id] = $this->workspaceTracker->getAllTrackedRevisions($workspace_id, $entity_type_id);
      }
      $all_associated_revisions = array_filter($all_associated_revisions);

      $count = 1;
      foreach ($all_associated_revisions as $entity_type_id => $associated_revisions) {
        /** @var \Drupal\Core\Entity\RevisionableStorageInterface $associated_entity_storage */
        $associated_entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

        // Sort the associated revisions in reverse ID order, so we can delete
        // the most recent revisions first.
        krsort($associated_revisions);

        // Get a list of default revisions tracked by the given workspace,
        // because they need to be handled differently than pending revisions.
        $initial_revision_ids = $this->workspaceTracker->getTrackedInitialRevisions($workspace_id, $entity_type_id);

        foreach (array_keys($associated_revisions) as $revision_id) {
          if ($count > $batch_size) {
            continue 2;
          }

          // If the workspace is tracking the entity's default revision (i.e.
          // the entity was created inside that workspace), we need to delete
          // the whole entity after all of its pending revisions are gone.
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
      // request a fresh list of tracked entities. If it is empty, we can go
      // ahead and remove the deleted workspace ID entry from state.
      $has_associated_revisions = FALSE;
      foreach (array_keys($this->workspaceInfo->getSupportedEntityTypes()) as $entity_type_id) {
        if (!empty($this->workspaceTracker->getAllTrackedRevisions($workspace_id, $entity_type_id))) {
          $has_associated_revisions = TRUE;
          break;
        }
      }
      if (!$has_associated_revisions) {
        unset($deleted_workspace_ids[$workspace_id]);
        $this->state->set('workspace.deleted', $deleted_workspace_ids);

        // Delete any possible leftover association entries.
        $this->workspaceTracker->deleteTrackedEntities($workspace_id);
      }
    });
  }

}
