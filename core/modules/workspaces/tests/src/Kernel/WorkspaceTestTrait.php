<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\workspaces\Entity\Workspace;

/**
 * A trait with common workspaces testing functionality.
 */
trait WorkspaceTestTrait {

  /**
   * The workspaces manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * An array of test workspaces, keyed by workspace ID.
   *
   * @var \Drupal\workspaces\WorkspaceInterface[]
   */
  protected $workspaces = [];

  /**
   * Enables the Workspaces module and creates two workspaces.
   */
  protected function initializeWorkspacesModule() {
    // Enable the Workspaces module here instead of the static::$modules array
    // so we can test it with default content.
    $this->enableModules(['workspaces']);
    $this->container = \Drupal::getContainer();
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->workspaceManager = \Drupal::service('workspaces.manager');

    $this->installEntitySchema('workspace');
    $this->installSchema('workspaces', ['workspace_association']);

    // Create two workspaces by default, 'live' and 'stage'.
    $this->workspaces['live'] = Workspace::create(['id' => 'live', 'label' => 'Live']);
    $this->workspaces['live']->save();
    $this->workspaces['stage'] = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $this->workspaces['stage']->save();

    $permissions = array_intersect([
      'administer nodes',
      'create workspace',
      'edit any workspace',
      'view any workspace',
    ], array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->setCurrentUser($this->createUser($permissions));
  }

  /**
   * Sets a given workspace as active.
   *
   * @param string $workspace_id
   *   The ID of the workspace to switch to.
   */
  protected function switchToWorkspace($workspace_id) {
    // Switch the test runner's context to the specified workspace.
    $workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id);
    \Drupal::service('workspaces.manager')->setActiveWorkspace($workspace);
  }

  /**
   * Returns all the revisions which are not associated with any workspace.
   *
   * @param string $entity_type_id
   *   An entity type ID to find revisions for.
   * @param int[]|string[]|null $entity_ids
   *   (optional) An array of entity IDs to filter the results by. Defaults to
   *   NULL.
   *
   * @return array
   *   An array of entity IDs, keyed by revision IDs.
   */
  protected function getUnassociatedRevisions($entity_type_id, $entity_ids = NULL) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    $query = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE)
      ->notExists($entity_type->get('revision_metadata_keys')['workspace']);

    if ($entity_ids) {
      $query->condition($entity_type->getKey('id'), $entity_ids, 'IN');
    }

    return $query->execute();
  }

}
