<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceTracker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests workspace tracker.
 */
#[CoversClass(WorkspaceTracker::class)]
#[Group('workspaces')]
#[RunTestsInSeparateProcesses]
class WorkspaceTrackerTest extends KernelTestBase {

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
    'entity_test',
    'user',
    'system',
    'workspaces',
    'workspaces_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('entity_test_mulrevpub_string_id');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    $this->installConfig(['system']);

    $this->installSchema('workspaces', ['workspace_association', 'workspace_association_revision']);

    $permissions = array_intersect([
      'administer nodes',
      'create workspace',
      'edit any workspace',
      'view any workspace',
    ], array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->setCurrentUser($this->createUser($permissions));

    $this->workspaces['qa'] = Workspace::create(['id' => 'qa', 'label' => 'QA']);
    $this->workspaces['qa']->save();
    $this->workspaces['preview'] = Workspace::create(['id' => 'preview', 'label' => 'Preview']);
    $this->workspaces['preview']->save();
    $this->workspaces['stage'] = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $this->workspaces['stage']->save();
    $this->workspaces['dev'] = Workspace::create(['id' => 'dev', 'parent' => 'stage', 'label' => 'Dev']);
    $this->workspaces['dev']->save();
  }

  /**
   * Tests the revisions tracked by a workspace.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to test.
   * @param array $entity_values
   *   An array of values for the entities created in this test.
   *
   * @legacy-covers ::getTrackedEntities
   * @legacy-covers ::getAssociatedRevisions
   */
  #[DataProvider('getEntityTypeIds')]
  public function testWorkspaceAssociation(string $entity_type_id, array $entity_values): void {
    $entity_1 = $this->createEntity($entity_type_id, $entity_values[1]);
    $this->createEntity($entity_type_id, $entity_values[2]);

    // Edit one of the existing nodes in 'stage'.
    $this->switchToWorkspace('stage');
    $entity_1->set('name', 'Test entity 1 - stage - published');
    $entity_1->setPublished();
    // This creates rev. 3.
    $entity_1->save();

    // Generate content with the following structure:
    // Stage:
    // - Test entity 3 - stage - unpublished (rev. 4)
    // - Test entity 4 - stage - published (rev. 5 and 6)
    $this->createEntity($entity_type_id, $entity_values[3]);
    $this->createEntity($entity_type_id, $entity_values[4]);

    $expected_latest_revisions = [
      'stage' => [3, 4, 6],
    ];
    $expected_all_revisions = [
      'stage' => [3, 4, 5, 6],
    ];
    $expected_initial_revisions = [
      'stage' => [4, 5],
    ];
    $this->assertWorkspaceAssociations($entity_type_id, $expected_latest_revisions, $expected_all_revisions, $expected_initial_revisions);

    // Dev:
    // - Test entity 1 - stage - published (rev. 3)
    // - Test entity 3 - stage - unpublished (rev. 4)
    // - Test entity 4 - stage - published (rev. 5 and 6)
    // - Test entity 5 - dev - unpublished (rev. 7)
    // - Test entity 6 - dev - published (rev. 8 and 9)
    $this->switchToWorkspace('dev');
    $this->createEntity($entity_type_id, $entity_values[5]);
    $this->createEntity($entity_type_id, $entity_values[6]);

    $expected_latest_revisions += [
      'dev' => [3, 4, 6, 7, 9],
    ];
    // Revisions 3, 4, 5 and 6 that were created in the parent 'stage' workspace
    // are also considered as being part of the child 'dev' workspace.
    $expected_all_revisions += [
      'dev' => [3, 4, 5, 6, 7, 8, 9],
    ];
    $expected_initial_revisions += [
      'dev' => [7, 8],
    ];
    $this->assertWorkspaceAssociations($entity_type_id, $expected_latest_revisions, $expected_all_revisions, $expected_initial_revisions);

    // Merge 'dev' into 'stage' and check the workspace associations.
    /** @var \Drupal\workspaces\WorkspaceMergerInterface $workspace_merger */
    $workspace_merger = \Drupal::service('workspaces.operation_factory')->getMerger($this->workspaces['dev'], $this->workspaces['stage']);
    $workspace_merger->merge();

    // The latest revisions from 'dev' are now tracked in 'stage'.
    $expected_latest_revisions['stage'] = $expected_latest_revisions['dev'];

    // Two revisions (8 and 9) were created for 'Test article 6', but only the
    // latest one (9) is being merged into 'stage'.
    $expected_all_revisions['stage'] = [3, 4, 5, 6, 7, 9];

    // Revision 7 was both an initial and latest revision in 'dev', so it is now
    // considered an initial revision in 'stage'.
    $expected_initial_revisions['stage'] = [4, 5, 7];

    // Which leaves revision 8 as the only remaining initial revision in 'dev'.
    $expected_initial_revisions['dev'] = [8];

    $this->assertWorkspaceAssociations($entity_type_id, $expected_latest_revisions, $expected_all_revisions, $expected_initial_revisions);

    // Publish 'stage' and check the workspace associations.
    /** @var \Drupal\workspaces\WorkspacePublisherInterface $workspace_publisher */
    $workspace_publisher = \Drupal::service('workspaces.operation_factory')->getPublisher($this->workspaces['stage']);
    $workspace_publisher->publish();

    $expected_revisions['stage'] = $expected_revisions['dev'] = [];
    $this->assertWorkspaceAssociations($entity_type_id, $expected_revisions, $expected_revisions, $expected_revisions);
  }

  /**
   * The data provider for ::testWorkspaceAssociation().
   */
  public static function getEntityTypeIds(): array {
    return [
      [
        'entity_type_id' => 'entity_test_mulrevpub',
        'entity_values' => [
          1 => ['name' => 'Test entity 1 - live - unpublished', 'status' => FALSE],
          2 => ['name' => 'Test entity 2 - live - published', 'status' => TRUE],
          3 => ['name' => 'Test entity 3 - stage - unpublished', 'status' => FALSE],
          4 => ['name' => 'Test entity 4 - stage - published', 'status' => TRUE],
          5 => ['name' => 'Test entity 5 - dev - unpublished', 'status' => FALSE],
          6 => ['name' => 'Test entity 6 - dev - published', 'status' => TRUE],
        ],
      ],
      [
        'entity_type_id' => 'entity_test_mulrevpub_string_id',
        'entity_values' => [
          1 => ['id' => 'test_1', 'name' => 'Test entity 1 - live - unpublished', 'status' => FALSE],
          2 => ['id' => 'test_2', 'name' => 'Test entity 2 - live - published', 'status' => TRUE],
          3 => ['id' => 'test_3', 'name' => 'Test entity 3 - stage - unpublished', 'status' => FALSE],
          4 => ['id' => 'test_4', 'name' => 'Test entity 4 - stage - published', 'status' => TRUE],
          5 => ['id' => 'test_5', 'name' => 'Test entity 5 - dev - unpublished', 'status' => FALSE],
          6 => ['id' => 'test_6', 'name' => 'Test entity 6 - dev - published', 'status' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Tests the count of revisions returned for tracked entities listing.
   *
   * @legacy-covers ::getTrackedEntitiesForListing
   */
  public function testWorkspaceAssociationForListing(): void {
    $this->switchToWorkspace($this->workspaces['stage']->id());
    $entity_type_id = 'entity_test_mulrevpub';

    for ($i = 1; $i <= 51; ++$i) {
      $this->createEntity($entity_type_id, ['name' => "Test entity {$i}"]);
    }

    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_association */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    // The default behavior uses a pager with 50 items per page.
    $tracked_items = $workspace_tracker->getTrackedEntitiesForListing($this->workspaces['stage']->id());
    $this->assertEquals(50, count($tracked_items[$entity_type_id]));

    // Verifies that all items are returned, not broken into pages.
    $tracked_items_no_pager = $workspace_tracker->getTrackedEntitiesForListing($this->workspaces['stage']->id(), NULL, FALSE);
    $this->assertEquals(51, count($tracked_items_no_pager[$entity_type_id]));
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
  protected function assertWorkspaceAssociations($entity_type_id, array $expected_latest_revisions, array $expected_all_revisions, array $expected_initial_revisions): void {
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');
    foreach ($expected_latest_revisions as $workspace_id => $expected_tracked_revision_ids) {
      $tracked_entities = $workspace_tracker->getTrackedEntities($workspace_id, $entity_type_id);
      $tracked_revision_ids = $tracked_entities[$entity_type_id] ?? [];
      $this->assertEquals($expected_tracked_revision_ids, array_keys($tracked_revision_ids));
    }

    foreach ($expected_all_revisions as $workspace_id => $expected_all_revision_ids) {
      $all_associated_revisions = $workspace_tracker->getAllTrackedRevisions($workspace_id, $entity_type_id);
      $this->assertEquals($expected_all_revision_ids, array_keys($all_associated_revisions));
    }

    foreach ($expected_initial_revisions as $workspace_id => $expected_initial_revision_ids) {
      $initial_revisions = $workspace_tracker->getTrackedInitialRevisions($workspace_id, $entity_type_id);
      $this->assertEquals($expected_initial_revision_ids, array_keys($initial_revisions));
    }
  }

  /**
   * Tests moving an entity with multiple revisions between workspaces.
   *
   * @legacy-covers ::moveTrackedEntities
   */
  public function testMoveTrackedEntitiesWithMultipleRevisions(): void {
    $entity_type_id = 'entity_test_mulrevpub';
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    // Get the workspace field name for later assertions.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    assert($entity_type instanceof ContentEntityTypeInterface);
    $workspace_field = $entity_type->getRevisionMetadataKey('workspace');
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Create an entity with multiple revisions in 'preview'.
    $this->switchToWorkspace('preview');
    $entity = $this->createEntity($entity_type_id, ['name' => 'Entity with revisions']);
    $entity->setName('Updated name 1');
    $entity->save();
    $entity->setName('Updated name 2');
    $entity->save();

    $preview_revisions = $workspace_tracker->getAllTrackedRevisions('preview', $entity_type_id);

    // Move the entity to 'qa'.
    $workspace_tracker->moveTrackedEntities('preview', 'qa', $entity_type_id, [$entity->id()]);

    // Verify all revisions have been moved.
    $this->assertEmpty($workspace_tracker->getAllTrackedRevisions('preview', $entity_type_id));

    $qa_revisions = $workspace_tracker->getAllTrackedRevisions('qa', $entity_type_id);
    $this->assertEquals($preview_revisions, $qa_revisions);

    // Verify the workspace field was updated on all revisions.
    foreach ($storage->loadMultipleRevisions(array_keys($qa_revisions)) as $revision) {
      $this->assertEquals('qa', $revision->{$workspace_field}->target_id);
    }
  }

  /**
   * Tests moving all entities of a specific type while leaving others.
   *
   * @legacy-covers ::moveTrackedEntities
   */
  public function testMoveTrackedEntitiesOfSpecificType(): void {
    $entity_type_id = 'entity_test_mulrevpub';
    $entity_type_id_string = 'entity_test_mulrevpub_string_id';
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    // Get the workspace field name for later assertions.
    $entity_type_string_id = $this->entityTypeManager->getDefinition($entity_type_id_string);
    $workspace_field_string_id = $entity_type_string_id->getRevisionMetadataKey('workspace');
    $storage_string_id = $this->entityTypeManager->getStorage($entity_type_id_string);

    // Create entities of different types in 'preview'.
    $this->switchToWorkspace('preview');

    $this->createEntity($entity_type_id, ['name' => 'Integer entity 1']);
    $this->createEntity($entity_type_id, ['name' => 'Integer entity 2']);
    $entity_string_1 = $this->createEntity($entity_type_id_string, ['id' => 'str_1', 'name' => 'String entity 1']);
    $entity_string_2 = $this->createEntity($entity_type_id_string, ['id' => 'str_2', 'name' => 'String entity 2']);

    // Move only the integer ID entities to 'qa'.
    $workspace_tracker->moveTrackedEntities('preview', 'qa', $entity_type_id);

    // Verify integer ID entities were moved.
    $this->assertEmpty($workspace_tracker->getTrackedEntities('preview', $entity_type_id)[$entity_type_id] ?? []);
    $this->assertCount(2, $workspace_tracker->getTrackedEntities('qa', $entity_type_id)[$entity_type_id]);

    // Verify string ID entities remain in 'preview'.
    $this->assertCount(2, $workspace_tracker->getTrackedEntities('preview', $entity_type_id_string)[$entity_type_id_string]);
    $this->assertEmpty($workspace_tracker->getTrackedEntities('qa', $entity_type_id_string)[$entity_type_id_string] ?? []);

    $preview_string_entities = $workspace_tracker->getTrackedEntities('preview', $entity_type_id_string);
    $this->assertContains($entity_string_1->id(), $preview_string_entities[$entity_type_id_string] ?? []);
    $this->assertContains($entity_string_2->id(), $preview_string_entities[$entity_type_id_string] ?? []);

    // Verify string entities workspace field is still 'preview'.
    $string_revision = $storage_string_id->loadRevision($entity_string_1->getRevisionId());
    $this->assertEquals('preview', $string_revision->{$workspace_field_string_id}->target_id);
  }

  /**
   * Tests moving all tracked entities of all types between workspaces.
   *
   * @legacy-covers ::moveTrackedEntities
   */
  public function testMoveAllTrackedEntities(): void {
    $entity_type_id = 'entity_test_mulrevpub';
    $entity_type_id_string = 'entity_test_mulrevpub_string_id';
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    // Create entities of different types in 'preview'.
    $this->switchToWorkspace('preview');
    $this->createEntity($entity_type_id, ['name' => 'Integer entity']);
    $this->createEntity($entity_type_id_string, ['id' => 'str_test', 'name' => 'String entity']);

    // Move all entities from 'preview' to 'qa'.
    $workspace_tracker->moveTrackedEntities('preview', 'qa');

    $this->assertEmpty($workspace_tracker->getTrackedEntities('preview', $entity_type_id)[$entity_type_id] ?? []);
    $this->assertEmpty($workspace_tracker->getTrackedEntities('preview', $entity_type_id_string)[$entity_type_id_string] ?? []);
    $this->assertCount(1, $workspace_tracker->getTrackedEntities('qa', $entity_type_id)[$entity_type_id]);
    $this->assertCount(1, $workspace_tracker->getTrackedEntities('qa', $entity_type_id_string)[$entity_type_id_string]);
  }

  /**
   * Tests validation for moveTrackedEntities().
   *
   * @legacy-covers ::moveTrackedEntities
   */
  #[DataProvider('providerMoveTrackedEntitiesValidation')]
  public function testMoveTrackedEntitiesValidation(
    string $source_workspace_id,
    string $target_workspace_id,
    ?string $entity_type_id,
    ?array $entity_ids,
    string $exception_class,
    string $exception_message,
  ): void {
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    $this->expectException($exception_class);
    $this->expectExceptionMessage($exception_message);

    $workspace_tracker->moveTrackedEntities($source_workspace_id, $target_workspace_id, $entity_type_id, $entity_ids);
  }

  /**
   * Data provider for testMoveTrackedEntitiesValidation().
   *
   * @return array
   *   Test cases with workspace IDs, entity type, entity IDs, and expected
   *   exceptions.
   */
  public static function providerMoveTrackedEntitiesValidation(): array {
    return [
      'same source and target workspace' => [
        'source_workspace_id' => 'qa',
        'target_workspace_id' => 'qa',
        'entity_type_id' => 'entity_test_mulrevpub',
        'entity_ids' => NULL,
        'exception_class' => \InvalidArgumentException::class,
        'exception_message' => 'Source and target workspace IDs cannot be the same.',
      ],
      'entity IDs without entity type' => [
        'source_workspace_id' => 'stage',
        'target_workspace_id' => 'qa',
        'entity_type_id' => NULL,
        'entity_ids' => [1, 2, 3],
        'exception_class' => \InvalidArgumentException::class,
        'exception_message' => 'Entity type ID must be provided when entity IDs are specified.',
      ],
      'source workspace is not top-level' => [
        'source_workspace_id' => 'dev',
        'target_workspace_id' => 'qa',
        'entity_type_id' => 'entity_test_mulrevpub',
        'entity_ids' => NULL,
        'exception_class' => \DomainException::class,
        'exception_message' => 'Both the source and target must be valid top-level workspaces.',
      ],
      'target workspace is not top-level' => [
        'source_workspace_id' => 'qa',
        'target_workspace_id' => 'dev',
        'entity_type_id' => 'entity_test_mulrevpub',
        'entity_ids' => NULL,
        'exception_class' => \DomainException::class,
        'exception_message' => 'Both the source and target must be valid top-level workspaces.',
      ],
      'source workspace has children' => [
        'source_workspace_id' => 'stage',
        'target_workspace_id' => 'qa',
        'entity_type_id' => 'entity_test_mulrevpub',
        'entity_ids' => NULL,
        'exception_class' => \DomainException::class,
        'exception_message' => 'Both the source and target must be valid top-level workspaces.',
      ],
    ];
  }

}
