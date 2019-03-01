<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests CRUD operations for workspaces.
 *
 * @group workspaces
 */
class WorkspaceCRUDTest extends KernelTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The workspace replication manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'system',
    'workspaces',
    'field',
    'filter',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpCurrentUser();

    $this->installSchema('system', ['key_value_expire']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('workspace_association');
    $this->installEntitySchema('node');

    $this->installConfig(['filter', 'node', 'system']);

    $this->createContentType(['type' => 'page']);

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->state = \Drupal::state();
    $this->workspaceManager = \Drupal::service('workspaces.manager');
  }

  /**
   * Tests the deletion of workspaces.
   */
  public function testDeletingWorkspaces() {
    $admin = $this->createUser([
      'administer nodes',
      'create workspace',
      'view any workspace',
      'edit any workspace',
      'delete any workspace',
    ]);
    $this->setCurrentUser($admin);

    /** @var \Drupal\workspaces\WorkspaceAssociationStorageInterface $workspace_association_storage */
    $workspace_association_storage = $this->entityTypeManager->getStorage('workspace_association');
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Create a workspace with a very small number of associated node revisions.
    $workspace_1 = Workspace::create([
      'id' => 'gibbon',
      'label' => 'Gibbon',
    ]);
    $workspace_1->save();
    $this->workspaceManager->setActiveWorkspace($workspace_1);

    $workspace_1_node_1 = $this->createNode(['status' => FALSE]);
    $workspace_1_node_2 = $this->createNode(['status' => FALSE]);
    for ($i = 0; $i < 4; $i++) {
      $workspace_1_node_1->setNewRevision(TRUE);
      $workspace_1_node_1->save();

      $workspace_1_node_2->setNewRevision(TRUE);
      $workspace_1_node_2->save();
    }

    // The workspace should have 10 associated node revisions, 5 for each node.
    $associated_revisions = $workspace_association_storage->getTrackedEntities($workspace_1->id(), TRUE);
    $this->assertCount(10, $associated_revisions['node']);

    // Check that we are allowed to delete the workspace.
    $this->assertTrue($workspace_1->access('delete', $admin));

    // Delete the workspace and check that all the workspace_association
    // entities and all the node revisions have been deleted as well.
    $workspace_1->delete();

    $associated_revisions = $workspace_association_storage->getTrackedEntities($workspace_1->id(), TRUE);
    $this->assertCount(0, $associated_revisions);
    $node_revision_count = $node_storage
      ->getQuery()
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals(0, $node_revision_count);

    // Create another workspace, this time with a larger number of associated
    // node revisions so we can test the batch purge process.
    $workspace_2 = Workspace::create([
      'id' => 'baboon',
      'label' => 'Baboon',
    ]);
    $workspace_2->save();
    $this->workspaceManager->setActiveWorkspace($workspace_2);

    $workspace_2_node_1 = $this->createNode(['status' => FALSE]);
    for ($i = 0; $i < 59; $i++) {
      $workspace_2_node_1->setNewRevision(TRUE);
      $workspace_2_node_1->save();
    }

    // The workspace should have 60 associated node revisions.
    $associated_revisions = $workspace_association_storage->getTrackedEntities($workspace_2->id(), TRUE);
    $this->assertCount(60, $associated_revisions['node']);

    // Delete the workspace and check that we still have 10 revision left to
    // delete.
    $workspace_2->delete();

    $associated_revisions = $workspace_association_storage->getTrackedEntities($workspace_2->id(), TRUE);
    $this->assertCount(10, $associated_revisions['node']);

    $workspace_deleted = \Drupal::state()->get('workspace.deleted');
    $this->assertCount(1, $workspace_deleted);

    // Check that we can not create another workspace with the same ID while its
    // data purging is not finished.
    $workspace_3 = Workspace::create([
      'id' => 'baboon',
      'label' => 'Baboon',
    ]);
    $violations = $workspace_3->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('A workspace with this ID has been deleted but data still exists for it.', $violations[0]->getMessage());

    // Running cron should delete the remaining data as well as the workspace ID
    // from the "workspace.delete" state entry.
    \Drupal::service('cron')->run();

    $associated_revisions = $workspace_association_storage->getTrackedEntities($workspace_2->id(), TRUE);
    $this->assertCount(0, $associated_revisions);
    $node_revision_count = $node_storage
      ->getQuery()
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals(0, $node_revision_count);

    $workspace_deleted = \Drupal::state()->get('workspace.deleted');
    $this->assertCount(0, $workspace_deleted);
  }

}
