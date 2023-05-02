<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests workspace associations.
 *
 * @coversDefaultClass \Drupal\workspaces\WorkspaceAssociation
 *
 * @group workspaces
 */
class WorkspaceAssociationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'text',
    'user',
    'system',
    'path_alias',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    $this->installConfig(['filter', 'node', 'system']);

    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('workspaces', ['workspace_association']);

    $this->createContentType(['type' => 'article']);

    $permissions = array_intersect([
      'administer nodes',
      'create workspace',
      'edit any workspace',
      'view any workspace',
    ], array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->setCurrentUser($this->createUser($permissions));

    $this->workspaces['stage'] = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $this->workspaces['stage']->save();
    $this->workspaces['dev'] = Workspace::create(['id' => 'dev', 'parent' => 'stage', 'label' => 'Dev']);
    $this->workspaces['dev']->save();
  }

  /**
   * Tests the revisions tracked by a workspace.
   *
   * @covers ::getTrackedEntities
   * @covers ::getAssociatedRevisions
   */
  public function testWorkspaceAssociation() {
    $this->createNode(['title' => 'Test article 1 - live - unpublished', 'type' => 'article', 'status' => 0]);
    $this->createNode(['title' => 'Test article 2 - live - published', 'type' => 'article']);

    // Edit one of the existing nodes in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('Test article 1 - stage - published');
    $node->setPublished();
    // This creates rev. 3.
    $node->save();

    // Generate content with the following structure:
    // Stage:
    // - Test article 3 - stage - unpublished (rev. 4)
    // - Test article 4 - stage - published (rev. 5 and 6)
    $this->createNode(['title' => 'Test article 3 - stage - unpublished', 'type' => 'article', 'status' => 0]);
    $this->createNode(['title' => 'Test article 4 - stage - published', 'type' => 'article']);

    $expected_latest_revisions = [
      'stage' => [3, 4, 6],
    ];
    $expected_all_revisions = [
      'stage' => [3, 4, 5, 6],
    ];
    $expected_initial_revisions = [
      'stage' => [4, 5],
    ];
    $this->assertWorkspaceAssociations('node', $expected_latest_revisions, $expected_all_revisions, $expected_initial_revisions);

    // Dev:
    // - Test article 1 - stage - published (rev. 3)
    // - Test article 3 - stage - unpublished (rev. 4)
    // - Test article 4 - stage - published (rev. 5 and 6)
    // - Test article 5 - dev - unpublished (rev. 7)
    // - Test article 6 - dev - published (rev. 8 and 9)
    $this->switchToWorkspace('dev');
    $this->createNode(['title' => 'Test article 5 - dev - unpublished', 'type' => 'article', 'status' => 0]);
    $this->createNode(['title' => 'Test article 6 - dev - published', 'type' => 'article']);

    $expected_latest_revisions += [
      'dev' => [3, 4, 6, 7, 9],
    ];
    // Revisions 3, 4, 5 and 6 that were created in the parent 'stage' workspace
    // are also considered as being part of the child 'dev' workspace.
    $expected_all_revisions += [
      'dev' => [3, 4, 5, 6, 7, 8, 9],
    ];
    $expected_initial_revisions += [
      'stage' => [7, 8],
    ];
    $this->assertWorkspaceAssociations('node', $expected_latest_revisions, $expected_all_revisions, $expected_initial_revisions);
  }

  /**
   * Checks the workspace associations for a test scenario.
   *
   * @param string $entity_type_id
   *   The ID of the entity type that is being tested.
   * @param array $expected_latest_revisions
   *   An array of expected values for the latest tracked revisions.
   * @param array $expected_all_revisions
   *   An array of expected values for all the tracked revisions.
   * @param array $expected_initial_revisions
   *   An array of expected values for the initial revisions, i.e. for the
   *   entities that were created in the specified workspace.
   */
  protected function assertWorkspaceAssociations($entity_type_id, array $expected_latest_revisions, array $expected_all_revisions, array $expected_initial_revisions) {
    $workspace_association = \Drupal::service('workspaces.association');
    foreach ($expected_latest_revisions as $workspace_id => $expected_tracked_revision_ids) {
      $tracked_entities = $workspace_association->getTrackedEntities($workspace_id, $entity_type_id);
      $tracked_revision_ids = $tracked_entities[$entity_type_id] ?? [];
      $this->assertEquals($expected_tracked_revision_ids, array_keys($tracked_revision_ids));
    }

    foreach ($expected_all_revisions as $workspace_id => $expected_all_revision_ids) {
      $all_associated_revisions = $workspace_association->getAssociatedRevisions($workspace_id, $entity_type_id);
      $this->assertEquals($expected_all_revision_ids, array_keys($all_associated_revisions));
    }

    foreach ($expected_initial_revisions as $workspace_id => $expected_initial_revision_ids) {
      $initial_revisions = $workspace_association->getAssociatedInitialRevisions($workspace_id, $entity_type_id);
      $this->assertEquals($expected_initial_revision_ids, array_keys($initial_revisions));
    }
  }

}
