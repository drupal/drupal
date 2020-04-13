<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityStorageException;
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
  use WorkspaceTestTrait;

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
  protected static $modules = [
    'user',
    'system',
    'workspaces',
    'field',
    'filter',
    'node',
    'text',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();

    $this->installSchema('system', ['key_value_expire']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('workspace');
    $this->installSchema('workspaces', ['workspace_association']);
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

    /** @var \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association */
    $workspace_association = \Drupal::service('workspaces.association');

    // Create a workspace with a very small number of associated node revisions.
    $workspace_1 = Workspace::create([
      'id' => 'gibbon',
      'label' => 'Gibbon',
    ]);
    $workspace_1->save();
    $this->workspaceManager->setActiveWorkspace($workspace_1);

    $workspace_1_node_1 = $this->createNode(['status' => FALSE]);
    $workspace_1_node_2 = $this->createNode(['status' => FALSE]);

    // The 'live' workspace should have 2 revisions now. The initial revision
    // for each node.
    $live_revisions = $this->getUnassociatedRevisions('node');
    $this->assertCount(2, $live_revisions);

    for ($i = 0; $i < 4; $i++) {
      $workspace_1_node_1->setNewRevision(TRUE);
      $workspace_1_node_1->save();

      $workspace_1_node_2->setNewRevision(TRUE);
      $workspace_1_node_2->save();
    }

    // The workspace should now track 2 nodes.
    $tracked_entities = $workspace_association->getTrackedEntities($workspace_1->id());
    $this->assertCount(2, $tracked_entities['node']);

    // There should still be 2 revisions associated with 'live'.
    $live_revisions = $this->getUnassociatedRevisions('node');
    $this->assertCount(2, $live_revisions);

    // The other 8 revisions should be associated with 'workspace_1'.
    $associated_revisions = $workspace_association->getAssociatedRevisions($workspace_1->id(), 'node');
    $this->assertCount(8, $associated_revisions);

    // Check that we are allowed to delete the workspace.
    $this->assertTrue($workspace_1->access('delete', $admin));

    // Delete the workspace and check that all the workspace_association
    // entities and all the node revisions have been deleted as well.
    $workspace_1->delete();

    // There are no more tracked entities in 'workspace_1'.
    $tracked_entities = $workspace_association->getTrackedEntities($workspace_1->id());
    $this->assertEmpty($tracked_entities);

    // There are no more revisions associated with 'workspace_1'.
    $associated_revisions = $workspace_association->getAssociatedRevisions($workspace_1->id(), 'node');
    $this->assertCount(0, $associated_revisions);

    // There should still be 2 revisions associated with 'live'.
    $live_revisions = $this->getUnassociatedRevisions('node');
    $this->assertCount(2, $live_revisions);

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

    // Now there is one entity tracked in 'workspace_2'.
    $tracked_entities = $workspace_association->getTrackedEntities($workspace_2->id());
    $this->assertCount(1, $tracked_entities['node']);

    // One revision of this entity is in 'live'.
    $live_revisions = $this->getUnassociatedRevisions('node', [$workspace_2_node_1->id()]);
    $this->assertCount(1, $live_revisions);

    // The other 59 are associated with 'workspace_2'.
    $associated_revisions = $workspace_association->getAssociatedRevisions($workspace_2->id(), 'node', [$workspace_2_node_1->id()]);
    $this->assertCount(59, $associated_revisions);

    // Delete the workspace and check that we still have 9 revision left to
    // delete.
    $workspace_2->delete();
    $associated_revisions = $workspace_association->getAssociatedRevisions($workspace_2->id(), 'node', [$workspace_2_node_1->id()]);
    $this->assertCount(9, $associated_revisions);

    // The live revision is also still there.
    $live_revisions = $this->getUnassociatedRevisions('node', [$workspace_2_node_1->id()]);
    $this->assertCount(1, $live_revisions);

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

    $associated_revisions = $workspace_association->getTrackedEntities($workspace_2->id());
    $this->assertCount(0, $associated_revisions);

    // 'workspace_2 'is empty now.
    $associated_revisions = $workspace_association->getAssociatedRevisions($workspace_2->id(), 'node', [$workspace_2_node_1->id()]);
    $this->assertCount(0, $associated_revisions);
    $tracked_entities = $workspace_association->getTrackedEntities($workspace_2->id());
    $this->assertEmpty($tracked_entities);

    // The 3 revisions in 'live' remain.
    $live_revisions = $this->getUnassociatedRevisions('node');
    $this->assertCount(3, $live_revisions);

    $workspace_deleted = \Drupal::state()->get('workspace.deleted');
    $this->assertCount(0, $workspace_deleted);
  }

  /**
   * Tests that deleting a workspace keeps its already published content.
   */
  public function testDeletingPublishedWorkspace() {
    $admin = $this->createUser([
      'administer nodes',
      'create workspace',
      'view own workspace',
      'edit own workspace',
      'delete own workspace',
    ]);
    $this->setCurrentUser($admin);

    $live_workspace = Workspace::create([
      'id' => 'live',
      'label' => 'Live',
    ]);
    $live_workspace->save();
    $workspace = Workspace::create([
      'id' => 'stage',
      'label' => 'Stage',
    ]);
    $workspace->save();
    $this->workspaceManager->setActiveWorkspace($workspace);

    // Create a new node in the 'stage' workspace
    $node = $this->createNode(['status' => TRUE]);

    // Create an additional workspace-specific revision for the node.
    $node->setNewRevision(TRUE);
    $node->save();

    // The node should have 3 revisions now: a default and 2 pending ones.
    $revisions = $this->entityTypeManager->getStorage('node')->loadMultipleRevisions([1, 2, 3]);
    $this->assertCount(3, $revisions);
    $this->assertTrue($revisions[1]->isDefaultRevision());
    $this->assertFalse($revisions[2]->isDefaultRevision());
    $this->assertFalse($revisions[3]->isDefaultRevision());

    // Publish the workspace, which should mark revision 3 as the default one
    // and keep revision 2 as a 'source' draft revision.
    $workspace->publish();
    $revisions = $this->entityTypeManager->getStorage('node')->loadMultipleRevisions([1, 2, 3]);
    $this->assertFalse($revisions[1]->isDefaultRevision());
    $this->assertFalse($revisions[2]->isDefaultRevision());
    $this->assertTrue($revisions[3]->isDefaultRevision());

    // Create two new workspace-revisions for the node.
    $node->setNewRevision(TRUE);
    $node->save();
    $node->setNewRevision(TRUE);
    $node->save();

    // The node should now have 5 revisions.
    $revisions = $this->entityTypeManager->getStorage('node')->loadMultipleRevisions([1, 2, 3, 4, 5]);
    $this->assertFalse($revisions[1]->isDefaultRevision());
    $this->assertFalse($revisions[2]->isDefaultRevision());
    $this->assertTrue($revisions[3]->isDefaultRevision());
    $this->assertFalse($revisions[4]->isDefaultRevision());
    $this->assertFalse($revisions[5]->isDefaultRevision());

    // Delete the workspace and check that only the two new pending revisions
    // were deleted by the workspace purging process.
    $workspace->delete();

    $revisions = $this->entityTypeManager->getStorage('node')->loadMultipleRevisions([1, 2, 3, 4, 5]);
    $this->assertCount(3, $revisions);
    $this->assertFalse($revisions[1]->isDefaultRevision());
    $this->assertFalse($revisions[2]->isDefaultRevision());
    $this->assertTrue($revisions[3]->isDefaultRevision());
    $this->assertFalse(isset($revisions[4]));
    $this->assertFalse(isset($revisions[5]));
  }

  /**
   * Tests that a workspace with children can not be deleted.
   */
  public function testDeletingWorkspaceWithChildren() {
    $stage = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $stage->save();

    $dev = Workspace::create(['id' => 'dev', 'label' => 'Dev', 'parent' => 'stage']);
    $dev->save();

    // Check that a workspace which has children can not be deleted.
    try {
      $stage->delete();
      $this->fail('The Stage workspace has children and should not be deletable.');
    }
    catch (EntityStorageException $e) {
      $this->assertEquals('The Stage workspace can not be deleted because it has child workspaces.', $e->getMessage());
      $this->assertNotNull(Workspace::load('stage'));
    }

    // Check that if we delete its child first, the parent workspace can also be
    // deleted.
    $dev->delete();
    $stage->delete();
    $this->assertNull(Workspace::load('dev'));
    $this->assertNull(Workspace::load('stage'));
  }

}
