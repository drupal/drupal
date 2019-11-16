<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests workspace merging.
 *
 * @coversDefaultClass \Drupal\workspaces\WorkspaceMerger
 *
 * @group workspaces
 */
class WorkspaceMergerTest extends KernelTestBase {

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
   * An array of nodes created before installing the Workspaces module.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->installConfig(['filter', 'node', 'system']);

    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('node', ['node_access']);

    $this->createContentType(['type' => 'article']);

    $this->setCurrentUser($this->createUser(['administer nodes']));
  }

  /**
   * Tests workspace merging.
   *
   * @covers ::merge
   * @covers ::getNumberOfChangesOnSource
   * @covers ::getNumberOfChangesOnTarget
   * @covers ::getDifferringRevisionIdsOnSource
   * @covers ::getDifferringRevisionIdsOnTarget
   */
  public function testWorkspaceMerger() {
    $this->initializeWorkspacesModule();
    $this->createWorkspaceHierarchy();

    // Generate content in the workspace hierarchy with the following structure:
    // Live:
    // - Test article 1 - live
    //
    // Stage:
    // - Test article 2 - stage
    //
    // Dev:
    // - Test article 2 - stage
    // - Test article 3 - dev
    //
    // Local 1:
    // - Test article 2 - stage
    // - Test article 3 - dev
    // - Test article 4 - local_1
    //
    // Local 2:
    // - Test article 2 - stage
    // - Test article 3 - dev
    //
    // Note that the contents of each workspace are inherited automatically in
    // each of its descendants.
    $this->createNode(['title' => 'Test article 1 - live', 'type' => 'article']);

    // This creates revisions 2 and 3. Revision 2 is an unpublished default
    // revision (which is also available in Live), and revision 3 is a published
    // pending revision that is available in Stage and all its descendants.
    $this->switchToWorkspace('stage');
    $this->createNode(['title' => 'Test article 2 - stage', 'type' => 'article']);

    $expected_workspace_association = [
      'stage' => [3],
      'dev' => [3],
      'local_1' => [3],
      'local_2' => [3],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create the second test article in Dev. This creates revisions 4 and 5.
    // Revision 4 is default and unpublished, and revision 5 is now being
    // tracked in Dev and its descendants.
    $this->switchToWorkspace('dev');
    $this->createNode(['title' => 'Test article 3 - dev', 'type' => 'article']);

    $expected_workspace_association = [
      'stage' => [3],
      'dev' => [3, 5],
      'local_1' => [3, 5],
      'local_2' => [3, 5],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create the third article in Local 1. This creates revisions 6 and 7.
    // Revision 6 is default and unpublished, and revision 7 is now being
    // tracked in the Local 1.
    $this->switchToWorkspace('local_1');
    $this->createNode(['title' => 'Test article 4 - local_1', 'type' => 'article']);

    $expected_workspace_association = [
      'stage' => [3],
      'dev' => [3, 5],
      'local_1' => [3, 5, 7],
      'local_2' => [3, 5],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    /** @var \Drupal\workspaces\WorkspaceMergerInterface $workspace_merger */
    $workspace_merger = \Drupal::service('workspaces.operation_factory')->getMerger($this->workspaces['local_1'], $this->workspaces['dev']);

    // Check that there is no content in Dev that's not also in Local 1.
    $this->assertEmpty($workspace_merger->getDifferringRevisionIdsOnTarget());
    $this->assertEquals(0, $workspace_merger->getNumberOfChangesOnTarget());

    // Check that there is only one node in Local 1 that's not available in Dev,
    // revision 7 created above for the fourth test article.
    $expected = [
      'node' => [7 => 4],
    ];
    $this->assertEquals($expected, $workspace_merger->getDifferringRevisionIdsOnSource());
    $this->assertEquals(1, $workspace_merger->getNumberOfChangesOnSource());

    // Merge the contents of Local 1 into Dev, and check that Dev, Local 1 and
    // Local 2 have the same content.
    $workspace_merger->merge();

    $this->assertEmpty($workspace_merger->getDifferringRevisionIdsOnTarget());
    $this->assertEquals(0, $workspace_merger->getNumberOfChangesOnTarget());
    $this->assertEmpty($workspace_merger->getDifferringRevisionIdsOnSource());
    $this->assertEquals(0, $workspace_merger->getNumberOfChangesOnSource());

    $this->switchToWorkspace('dev');
    $expected_workspace_association = [
      'stage' => [3],
      'dev' => [3, 5, 7],
      'local_1' => [3, 5, 7],
      'local_2' => [3, 5, 7],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    $workspace_merger = \Drupal::service('workspaces.operation_factory')->getMerger($this->workspaces['local_1'], $this->workspaces['stage']);

    // Check that there is no content in Stage that's not also in Local 1.
    $this->assertEmpty($workspace_merger->getDifferringRevisionIdsOnTarget());
    $this->assertEquals(0, $workspace_merger->getNumberOfChangesOnTarget());

    // Check that the difference between Local 1 and Stage are the two revisions
    // for 'Test article 3 - dev' and 'Test article 4 - local_1'.
    $expected = [
      'node' => [
        5 => 3,
        7 => 4,
      ],
    ];
    $this->assertEquals($expected, $workspace_merger->getDifferringRevisionIdsOnSource());
    $this->assertEquals(2, $workspace_merger->getNumberOfChangesOnSource());

    // Check that Local 1 can not be merged directly into Stage, since it can
    // only be merged into its direct parent.
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The contents of a workspace can only be merged into its parent workspace.');
    $workspace_merger->merge();
  }

}
