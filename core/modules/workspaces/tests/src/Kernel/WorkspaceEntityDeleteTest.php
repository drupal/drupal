<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests entity deletions with workspaces.
 *
 * @group workspaces
 */
class WorkspaceEntityDeleteTest extends KernelTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'system',
    'text',
    'user',
    'workspaces',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->workspaceManager = \Drupal::service('workspaces.manager');

    $this->installEntitySchema('node');
    $this->installEntitySchema('workspace');

    $this->installSchema('node', ['node_access']);
    $this->installSchema('workspaces', ['workspace_association']);

    $this->installConfig(['filter', 'node', 'system']);

    $this->createContentType(['type' => 'page']);

    $this->setUpCurrentUser([], [
      'access content',
      'create page content',
      'edit any page content',
      'delete any page content',
      'create workspace',
      'view any workspace',
      'edit any workspace',
      'delete any workspace',
    ]);
  }

  /**
   * Test entity deletion in a workspace.
   */
  public function testEntityDeletion(): void {
    /** @var \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association */
    $workspace_association = \Drupal::service('workspaces.association');
    $storage = $this->entityTypeManager->getStorage('node');

    $published_live = $this->createNode(['title' => 'Test 1 published - live', 'type' => 'page']);
    $unpublished_live = $this->createNode(['title' => 'Test 2 unpublished - live', 'type' => 'page', 'status' => FALSE]);

    // Create a published and an unpublished node in Stage.
    Workspace::create(['id' => 'stage', 'label' => 'Stage'])->save();
    $this->switchToWorkspace('stage');

    $published_stage = $this->createNode(['title' => 'Test 3 published - stage', 'type' => 'page']);
    $unpublished_stage = $this->createNode(['title' => 'Test 4 unpublished - stage', 'type' => 'page', 'status' => FALSE]);
    $this->assertEquals(['node' => [4 => 3, 5 => 4]], $workspace_association->getTrackedEntities('stage', 'node'));
    $this->assertTrue($published_stage->access('delete'));
    $this->assertTrue($unpublished_stage->access('delete'));

    // While the Stage workspace is active, check that the nodes created in
    // Stage can be deleted, while the ones created in Live can not be deleted.
    $published_stage->delete();
    $this->assertEquals(['node' => [5 => 4]], $workspace_association->getTrackedEntities('stage', 'node'));

    $unpublished_stage->delete();
    $this->assertEmpty($workspace_association->getTrackedEntities('stage', 'node'));
    $this->assertEmpty($storage->loadMultiple([$published_stage->id(), $unpublished_stage->id()]));

    $this->expectExceptionMessage('This content item can only be deleted in the Live workspace.');
    $this->assertFalse($published_live->access('delete'));
    $this->assertFalse($unpublished_live->access('delete'));
    $published_live->delete();
    $unpublished_live->delete();
    $this->assertNotEmpty($storage->loadMultiple([$published_live->id(), $unpublished_live->id()]));
  }

}
