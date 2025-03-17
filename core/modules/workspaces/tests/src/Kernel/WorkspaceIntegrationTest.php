<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

// cspell:ignore differring

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\form_test\Form\FormTestAlterForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspacePublishException;

/**
 * Tests a complete publishing scenario across different workspaces.
 *
 * @group workspaces
 */
class WorkspaceIntegrationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceFieldCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;
  use WorkspaceTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creation timestamp that should be incremented for each new entity.
   *
   * @var int
   */
  protected $createdTimestamp;

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
    'entity_test',
    'field',
    'filter',
    'node',
    'text',
    'user',
    'system',
    'views',
    'language',
    'content_translation',
    'path_alias',
    'form_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('entity_test_no_label');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->installConfig(['filter', 'node', 'system', 'language', 'content_translation']);

    $this->installSchema('node', ['node_access']);

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    $this->createContentType(['type' => 'page']);

    $this->setCurrentUser($this->createUser(['administer nodes']));

    $this->container->get('content_translation.manager')->setEnabled('node', 'page', TRUE);

    // Create two nodes, a published and an unpublished one, so we can test the
    // behavior of the module with default/existing content.
    $this->createdTimestamp = \Drupal::time()->getRequestTime();
    $this->nodes[] = $this->createNode([
      'title' => 'live - 1 - r1 - published',
      'body' => 'node 1',
      'created' => $this->createdTimestamp++,
      'status' => TRUE,
    ]);
    $this->nodes[] = $this->createNode([
      'title' => 'live - 2 - r2 - unpublished',
      'body' => 'node 2',
      'created' => $this->createdTimestamp++,
      'status' => FALSE,
    ]);

    $translation = $this->nodes[0]->addTranslation('de');
    $translation->setTitle('live - 1 - r1 - published - de');
    $translation->save();
  }

  /**
   * Tests various scenarios for creating and publishing content in workspaces.
   */
  public function testWorkspaces(): void {
    $this->initializeWorkspacesModule();

    // Notes about the structure of the test scenarios:
    // - a multi-dimensional array keyed by the workspace ID, then by the entity
    //   ID and finally by the revision ID.
    // - 'default_revision' indicates the entity revision that should be
    //   returned when loading an entity, non-revision entity queries and
    //   non-revision views *in a given workspace*, it does not indicate what is
    //   actually stored in the base and data entity tables.
    $test_scenarios = [];

    // The $expected_workspace_association array holds the revision IDs which
    // should be tracked by the Workspace Association entity type in each test
    // scenario, keyed by workspace ID.
    $expected_workspace_association = [];

    // In the initial state we have only the two revisions that were created
    // before the Workspaces module was installed.
    $revision_state = [
      'live' => [
        1 => [
          1 => [
            'title' => 'live - 1 - r1 - published',
            'status' => TRUE,
            'default_revision' => TRUE,
          ],
        ],
        2 => [
          2 => [
            'title' => 'live - 2 - r2 - unpublished',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
        ],
      ],
      'stage' => [
        1 => [
          1 => [
            'title' => 'live - 1 - r1 - published',
            'status' => TRUE,
            'default_revision' => TRUE,
          ],
        ],
        2 => [
          2 => [
            'title' => 'live - 2 - r2 - unpublished',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
        ],
      ],
    ];
    $test_scenarios['initial_state'] = $revision_state;
    $expected_workspace_association['initial_state'] = ['stage' => []];

    // Unpublish node 1 in 'stage'. The new revision is also added to 'live' but
    // it is not the default revision.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        1 => [
          3 => [
            'title' => 'stage - 1 - r3 - unpublished',
            'status' => FALSE,
            'default_revision' => FALSE,
          ],
        ],
      ],
      'stage' => [
        1 => [
          1 => ['default_revision' => FALSE],
          3 => [
            'title' => 'stage - 1 - r3 - unpublished',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
        ],
      ],
    ]);
    $test_scenarios['unpublish_node_1_in_stage'] = $revision_state;
    $expected_workspace_association['unpublish_node_1_in_stage'] = ['stage' => [3]];

    // Publish node 2 in 'stage'. The new revision is also added to 'live' but
    // it is not the default revision.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        2 => [
          4 => [
            'title' => 'stage - 2 - r4 - published',
            'status' => TRUE,
            'default_revision' => FALSE,
          ],
        ],
      ],
      'stage' => [
        2 => [
          2 => ['default_revision' => FALSE],
          4 => [
            'title' => 'stage - 2 - r4 - published',
            'status' => TRUE,
            'default_revision' => TRUE,
          ],
        ],
      ],
    ]);
    $test_scenarios['publish_node_2_in_stage'] = $revision_state;
    $expected_workspace_association['publish_node_2_in_stage'] = ['stage' => [3, 4]];

    // Adding a new unpublished node on 'stage' should create a single
    // unpublished revision on both 'stage' and 'live'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        3 => [
          5 => [
            'title' => 'stage - 3 - r5 - unpublished',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
        ],
      ],
      'stage' => [
        3 => [
          5 => [
            'title' => 'stage - 3 - r5 - unpublished',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
        ],
      ],
    ]);
    $test_scenarios['add_unpublished_node_in_stage'] = $revision_state;
    $expected_workspace_association['add_unpublished_node_in_stage'] = ['stage' => [3, 4, 5]];

    // Adding a new published node on 'stage' should create two revisions, an
    // unpublished revision on 'live' and a published one on 'stage'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        4 => [
          6 => [
            'title' => 'stage - 4 - r6 - published',
            'status' => FALSE,
            'default_revision' => TRUE,
          ],
          7 => [
            'title' => 'stage - 4 - r6 - published',
            'status' => TRUE,
            'default_revision' => FALSE,
          ],
        ],
      ],
      'stage' => [
        4 => [
          6 => [
            'title' => 'stage - 4 - r6 - published',
            'status' => FALSE,
            'default_revision' => FALSE,
          ],
          7 => [
            'title' => 'stage - 4 - r6 - published',
            'status' => TRUE,
            'default_revision' => TRUE,
          ],
        ],
      ],
    ]);
    $test_scenarios['add_published_node_in_stage'] = $revision_state;
    $expected_workspace_association['add_published_node_in_stage'] = ['stage' => [3, 4, 5, 7]];

    // Publishing 'stage' to 'live' should simply make the latest revisions in
    // 'stage' the default ones in 'live'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        1 => [
          1 => ['default_revision' => FALSE],
          3 => ['default_revision' => TRUE],
        ],
        2 => [
          2 => ['default_revision' => FALSE],
          4 => ['default_revision' => TRUE],
        ],
        // Node 3 has a single revision for both 'stage' and 'live' and it is
        // already the default revision in both of them.
        4 => [
          6 => ['default_revision' => FALSE],
          7 => ['default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['push_stage_to_live'] = $revision_state;
    $expected_workspace_association['push_stage_to_live'] = ['stage' => []];

    // Check the initial state after the module was installed.
    $this->assertWorkspaceStatus($test_scenarios['initial_state'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['initial_state'], 'node');

    // Unpublish node 1 in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('stage - 1 - r3 - unpublished');
    $node->setUnpublished();
    $node->save();
    $this->assertWorkspaceStatus($test_scenarios['unpublish_node_1_in_stage'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['unpublish_node_1_in_stage'], 'node');

    // Publish node 2 in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(2);
    $node->setTitle('stage - 2 - r4 - published');
    $node->setPublished();
    $node->save();
    $this->assertWorkspaceStatus($test_scenarios['publish_node_2_in_stage'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['publish_node_2_in_stage'], 'node');

    // Add a new unpublished node on 'stage'.
    $this->switchToWorkspace('stage');
    $this->createNode([
      'title' => 'stage - 3 - r5 - unpublished',
      'created' => $this->createdTimestamp++,
      'status' => FALSE,
    ]);
    $this->assertWorkspaceStatus($test_scenarios['add_unpublished_node_in_stage'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['add_unpublished_node_in_stage'], 'node');

    // Add a new published node on 'stage'.
    $this->switchToWorkspace('stage');
    $this->createNode([
      'title' => 'stage - 4 - r6 - published',
      'created' => $this->createdTimestamp++,
      'status' => TRUE,
    ]);
    $this->assertWorkspaceStatus($test_scenarios['add_published_node_in_stage'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['add_published_node_in_stage'], 'node');

    // Publish 'stage' to 'live'.
    /** @var \Drupal\workspaces\WorkspacePublisher $workspace_publisher */
    $workspace_publisher = \Drupal::service('workspaces.operation_factory')->getPublisher($this->workspaces['stage']);

    // Check which revisions need to be pushed.
    $expected = [
      'node' => [
        3 => 1,
        4 => 2,
        5 => 3,
        7 => 4,
      ],
    ];
    $this->assertEquals($expected, $workspace_publisher->getDifferringRevisionIdsOnSource());

    $this->workspaces['stage']->publish();
    $this->assertWorkspaceStatus($test_scenarios['push_stage_to_live'], 'node');
    $this->assertWorkspaceAssociation($expected_workspace_association['push_stage_to_live'], 'node');

    // Check that all the revisions that were published to 'Live' were also
    // marked as default revisions in their revision metadata field.
    $published_revisions = $this->entityTypeManager->getStorage('node')->loadMultipleRevisions(array_keys($expected['node']));
    foreach ($published_revisions as $published_revision) {
      $this->assertTrue($published_revision->wasDefaultRevision());
    }

    // Check that there are no more revisions to push.
    $this->assertEmpty($workspace_publisher->getDifferringRevisionIdsOnSource());
  }

  /**
   * Tests the workspace association data integrity for entity CRUD operations.
   *
   * @covers \Drupal\workspaces\Hook\EntityOperations::entityPresave
   * @covers \Drupal\workspaces\Hook\EntityOperations::entityInsert
   * @covers \Drupal\workspaces\Hook\EntityOperations::entityDelete
   * @covers \Drupal\workspaces\Hook\EntityOperations::entityRevisionDelete
   */
  public function testWorkspaceAssociationDataIntegrity(): void {
    $this->initializeWorkspacesModule();

    // Check the initial empty state.
    $expected_workspace_association = ['stage' => []];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Add a new unpublished node in 'stage' and check that new revision is
    // tracked in the workspace association data.
    $this->switchToWorkspace('stage');
    $unpublished_node = $this->createNode([
      'title' => 'stage - 3 - r3 - unpublished',
      'created' => $this->createdTimestamp++,
      'status' => FALSE,
    ]);
    $expected_workspace_association = ['stage' => [3]];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Add a new revision for the unpublished node.
    $unpublished_node->title = 'stage - 3 - r4 - unpublished';
    $unpublished_node->save();
    $expected_workspace_association = ['stage' => [4]];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Delete the unpublished node and check that the association data has been
    // updated.
    $unpublished_node->delete();
    $expected_workspace_association = ['stage' => []];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Add a new published node in 'stage' and check that new workspace-specific
    // revision is tracked in the workspace association data. Note that revision
    // '5' has been created as an unpublished default revision in Live, so it is
    // not tracked.
    $node = $this->createNode([
      'title' => 'stage - 4 - r6 - published',
      'created' => $this->createdTimestamp++,
      'status' => TRUE,
    ]);
    $expected_workspace_association = ['stage' => [6]];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Check that the entity object that was saved has been updated to point to
    // the workspace-specific revision.
    $this->assertEquals(6, $node->getRevisionId());

    // Delete revision '6' and check that the workspace association does not
    // track it anymore.
    $this->entityTypeManager->getStorage('node')->deleteRevision(6);
    $expected_workspace_association = ['stage' => []];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');
  }

  /**
   * Tests entity tracking in workspace descendants.
   */
  public function testWorkspaceHierarchy(): void {
    $this->initializeWorkspacesModule();
    $this->createWorkspaceHierarchy();

    // The two pre-existing nodes are not overridden in any non-default
    // workspace.
    $expected_workspace_association = [
      'stage' => [],
      'dev' => [],
      'local_1' => [],
      'local_2' => [],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create a new revision for a node in 'stage' and check that it's tracked
    // in all descendants.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('stage - 1 - r3');
    $node->save();

    $expected_workspace_association = [
      'stage' => [3],
      'dev' => [3],
      'local_1' => [3],
      'local_2' => [3],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create a new published node in 'stage' (which creates two revisions), and
    // check that it's tracked in all its descendants.
    $this->switchToWorkspace('stage');
    $this->createNode([
      'title' => 'stage - 3 - r5 - published',
      'created' => $this->createdTimestamp++,
      'status' => TRUE,
    ]);
    $expected_workspace_association = [
      'stage' => [3, 5],
      'dev' => [3, 5],
      'local_1' => [3, 5],
      'local_2' => [3, 5],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create a new revision in `dev` and check that it's also tracked in
    // 'local_1' and 'local_2'.
    $this->switchToWorkspace('dev');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('dev - 1 - r6');
    $node->save();

    $expected_workspace_association = [
      'stage' => [3, 5],
      'dev' => [5, 6],
      'local_1' => [5, 6],
      'local_2' => [5, 6],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Create a new revision in 'local_1', then a different one in 'dev', which
    // should only be tracked in `dev` and 'local_2', because 'local_1' is no
    // longer inheriting from 'dev'.
    $this->switchToWorkspace('local_1');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('local_1 - 1 - r7');
    $node->save();

    $expected_workspace_association = [
      'stage' => [3, 5],
      'dev' => [5, 6],
      'local_1' => [5, 7],
      'local_2' => [5, 6],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    $this->switchToWorkspace('dev');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('dev - 1 - r8');
    $node->save();

    $expected_workspace_association = [
      'stage' => [3, 5],
      'dev' => [5, 8],
      'local_1' => [5, 7],
      'local_2' => [5, 8],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Add a child workspace for 'dev' and check that it inherits all the parent
    // associations by default.
    $this->workspaces['local_3'] = Workspace::create(['id' => 'local_3', 'parent' => 'dev']);
    $this->workspaces['local_3']->save();

    $expected_workspace_association = [
      'stage' => [3, 5],
      'dev' => [5, 8],
      'local_1' => [5, 7],
      'local_2' => [5, 8],
      'local_3' => [5, 8],
      'qa' => [],
    ];
    $this->assertWorkspaceAssociation($expected_workspace_association, 'node');

    // Check that a workspace that is not at the top level can not be published.
    $this->expectException(WorkspacePublishException::class);
    $this->expectExceptionMessage('Only top-level workspaces can be published.');
    $this->workspaces['dev']->publish();
  }

  /**
   * Tests workspace publishing as anonymous user, simulating a CLI request.
   */
  public function testCliPublishing(): void {
    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');

    // Add a workspace-specific revision to a pre-existing node.
    $node = $this->entityTypeManager->getStorage('node')->load(2);
    $node->title->value = 'stage - 2 - r3 - published';
    $node->save();

    // Switch to an anonymous user account and the 'Live' workspace.
    \Drupal::service('account_switcher')->switchTo(new AnonymousUserSession());
    \Drupal::service('workspaces.manager')->switchToLive();

    // Publish the workspace as anonymous, simulating a CLI request.
    $this->workspaces['stage']->publish();
  }

  /**
   * Tests entity query overrides without any conditions.
   */
  public function testEntityQueryWithoutConditions(): void {
    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');

    // Add a workspace-specific revision to a pre-existing node.
    $node = $this->entityTypeManager->getStorage('node')->load(2);
    $node->title->value = 'stage - 2 - r3 - published';
    $node->save();

    $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck(FALSE);
    $query->sort('nid');
    $query->pager(1);
    $result = $query->execute();

    $this->assertSame([1 => '1'], $result);

    $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck(FALSE);
    $query->sort('nid', 'DESC');
    $query->pager(10);
    $result = $query->execute();

    $this->assertSame([3 => '2', 1 => '1'], $result);
  }

  /**
   * Tests the Entity Query relationship API with workspaces.
   */
  public function testEntityQueryRelationship(): void {
    $this->initializeWorkspacesModule();

    // Add an entity reference field that targets 'entity_test_mulrevpub'
    // entities.
    $this->createEntityReferenceField('node', 'page', 'field_test_entity', 'Test entity reference', 'entity_test_mulrevpub');

    // Add an entity reference field that targets 'node' entities so we can test
    // references to the same base tables.
    $this->createEntityReferenceField('node', 'page', 'field_test_node', 'Test node reference', 'node');

    $this->switchToWorkspace('live');
    $node_1 = $this->createNode([
      'title' => 'live node 1',
    ]);
    $entity_test = EntityTestMulRevPub::create([
      'name' => 'live entity_test_mulrevpub',
      'non_rev_field' => 'live non-revisionable value',
    ]);
    $entity_test->save();

    $node_2 = $this->createNode([
      'title' => 'live node 2',
      'field_test_entity' => $entity_test->id(),
      'field_test_node' => $node_1->id(),
    ]);

    // Switch to the 'stage' workspace and change some values for the referenced
    // entities.
    $this->switchToWorkspace('stage');
    $node_1->title->value = 'stage node 1';
    $node_1->save();

    $node_2->title->value = 'stage node 2';
    $node_2->save();

    $entity_test->name->value = 'stage entity_test_mulrevpub';
    $entity_test->non_rev_field->value = 'stage non-revisionable value';
    $entity_test->save();

    // Make sure that we're requesting the default revision.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck(FALSE);
    $query->currentRevision();

    $query
      // Check a condition on the revision data table.
      ->condition('title', 'stage node 2')
      // Check a condition on the revision table.
      ->condition('revision_uid', $node_2->getRevisionUserId())
      // Check a condition on the data table.
      ->condition('type', $node_2->bundle())
      // Check a condition on the base table.
      ->condition('uuid', $node_2->uuid());

    // Add conditions for a reference to the same entity type.
    $query
      // Check a condition on the revision data table.
      ->condition('field_test_node.entity.title', 'stage node 1')
      // Check a condition on the revision table.
      ->condition('field_test_node.entity.revision_uid', $node_1->getRevisionUserId())
      // Check a condition on the data table.
      ->condition('field_test_node.entity.type', $node_1->bundle())
      // Check a condition on the base table.
      ->condition('field_test_node.entity.uuid', $node_1->uuid());

    // Add conditions for a reference to a different entity type.
    // @todo Re-enable the two conditions below when we find a way to not join
    //   the workspace_association table for every duplicate entity base table
    //   join.
    // @see https://www.drupal.org/project/drupal/issues/2983639
    $query
      // Check a condition on the revision data table.
      // ->condition('field_test_entity.entity.name', 'stage entity_test_mulrevpub')
      // Check a condition on the data table.
      // ->condition('field_test_entity.entity.non_rev_field', 'stage non-revisionable value')
      // Check a condition on the base table.
      ->condition('field_test_entity.entity.uuid', $entity_test->uuid());

    $result = $query->execute();
    $this->assertSame([$node_2->getRevisionId() => $node_2->id()], $result);
  }

  /**
   * Tests CREATE operations for unsupported entity types.
   *
   * @dataProvider providerTestAllowedEntityCrudInNonDefaultWorkspace
   */
  public function testDisallowedEntityCreateInNonDefaultWorkspace($entity_type_id, $allowed): void {
    $this->initializeWorkspacesModule();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Switch to a non-default workspace and check whether creating a new entity
    // is allowed.
    $this->switchToWorkspace('stage');

    $entity = $storage->createWithSampleValues($entity_type_id);
    if ($entity_type_id === 'workspace') {
      $entity->id = 'test';
    }

    if (!$allowed) {
      $this->expectException(EntityStorageException::class);
      $this->expectExceptionMessage("The \"$entity_type_id\" entity type can only be saved in the default workspace.");
    }
    $entity->save();
  }

  /**
   * Tests UPDATE operations for unsupported entity types.
   *
   * @dataProvider providerTestAllowedEntityCrudInNonDefaultWorkspace
   */
  public function testDisallowedEntityUpdateInNonDefaultWorkspace($entity_type_id, $allowed): void {
    $this->initializeWorkspacesModule();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Create the entity in the default workspace.
    $this->workspaceManager->switchToLive();
    $entity = $storage->createWithSampleValues($entity_type_id);
    if ($entity_type_id === 'workspace') {
      $entity->id = 'test';
    }
    $entity->save();

    // Switch to a non-default workspace and check whether updating the entity
    // is allowed.
    $this->switchToWorkspace('stage');

    $entity->created->value = 1;

    if (!$allowed) {
      $this->expectException(EntityStorageException::class);
      $this->expectExceptionMessage("The \"$entity_type_id\" entity type can only be saved in the default workspace.");
    }
    $entity->save();
  }

  /**
   * Tests DELETE operations for unsupported entity types.
   *
   * @dataProvider providerTestAllowedEntityCrudInNonDefaultWorkspace
   */
  public function testDisallowedEntityDeleteInNonDefaultWorkspace($entity_type_id, $allowed): void {
    $this->initializeWorkspacesModule();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Create the entity in the default workspace.
    $this->workspaceManager->switchToLive();
    $entity = $storage->createWithSampleValues($entity_type_id);
    if ($entity_type_id === 'workspace') {
      $entity->id = 'test';
    }
    $entity->save();

    // Switch to a non-default workspace and check whether deleting the entity
    // is allowed.
    $this->switchToWorkspace('stage');

    if (!$allowed) {
      $this->expectException(EntityStorageException::class);
      $this->expectExceptionMessage("This {$entity->getEntityType()->getSingularLabel()} can only be deleted in the Live workspace.");
    }
    $entity->delete();
  }

  /**
   * Data provider for allowed entity CRUD operations.
   */
  public static function providerTestAllowedEntityCrudInNonDefaultWorkspace() {
    return [
      'workspace-provided non-internal entity type' => [
        'entity_type_id' => 'workspace',
        'allowed' => TRUE,
      ],
      'internal entity type' => [
        'entity_type_id' => 'entity_test_no_label',
        'allowed' => TRUE,
      ],
      'non-internal entity type' => [
        'entity_type_id' => 'entity_test_mulrev',
        'allowed' => FALSE,
      ],
    ];
  }

  /**
   * @covers \Drupal\workspaces\WorkspaceManager::executeInWorkspace
   */
  public function testExecuteInWorkspaceContext(): void {
    $this->initializeWorkspacesModule();

    // Create an entity in the default workspace.
    $this->workspaceManager->switchToLive();
    $node = $this->createNode([
      'title' => 'live node 1',
    ]);
    $node->save();

    // Switch to the 'stage' workspace and change some values for the referenced
    // entities.
    $this->switchToWorkspace('stage');
    $node->title->value = 'stage node 1';
    $node->save();

    // Switch back to the default workspace and run the baseline assertions.
    $this->workspaceManager->switchToLive();
    $storage = $this->entityTypeManager->getStorage('node');

    $this->assertFalse($this->workspaceManager->hasActiveWorkspace());

    $live_node = $storage->load($node->id());
    $this->assertEquals('live node 1', $live_node->title->value);

    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('title', 'live node 1')
      ->execute();
    $this->assertEquals([$live_node->getRevisionId() => $node->id()], $result);

    // Try the same assertions in the context of the 'stage' workspace.
    $this->workspaceManager->executeInWorkspace('stage', function () use ($node, $storage) {
      $this->assertEquals('stage', $this->workspaceManager->getActiveWorkspace()->id());

      $stage_node = $storage->load($node->id());
      $this->assertEquals('stage node 1', $stage_node->title->value);

      $result = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('title', 'stage node 1')
        ->execute();
      $this->assertEquals([$stage_node->getRevisionId() => $stage_node->id()], $result);
    });

    // Check that the 'stage' workspace was not persisted by the workspace
    // manager.
    $this->assertFalse($this->workspaceManager->getActiveWorkspace());
  }

  /**
   * Checks entity load, entity queries and views results for a test scenario.
   *
   * @param array $expected
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type that is being tested.
   *
   * @internal
   */
  protected function assertWorkspaceStatus(array $expected, string $entity_type_id): void {
    $expected = $this->flattenExpectedValues($expected, $entity_type_id);

    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    foreach ($expected as $workspace_id => $expected_values) {
      $this->switchToWorkspace($workspace_id);

      // Check that default revisions are swapped with the workspace revision.
      $this->assertEntityLoad($expected_values, $entity_type_id);

      // Check that non-default revisions are not changed.
      $this->assertEntityRevisionLoad($expected_values, $entity_type_id);

      // Check that entity queries return the correct results.
      $this->assertEntityQuery($expected_values, $entity_type_id);

      // Check that the 'Frontpage' view only shows published content that is
      // also considered as the default revision in the given workspace.
      $expected_frontpage = array_filter($expected_values, function ($expected_value) {
        return $expected_value['status'] === TRUE && $expected_value['default_revision'] === TRUE;
      });
      // The 'Frontpage' view will output nodes in reverse creation order.
      usort($expected_frontpage, function ($a, $b) {
        return $b['nid'] - $a['nid'];
      });
      $view = Views::getView('frontpage');
      $view->execute();
      $this->assertIdenticalResultset($view, $expected_frontpage, ['nid' => 'nid']);

      $rendered_view = $view->render('page_1');
      $output = \Drupal::service('renderer')->renderRoot($rendered_view);
      $this->setRawContent($output);
      foreach ($expected_values as $expected_entity_values) {
        if ($expected_entity_values[$entity_keys['published']] === TRUE && $expected_entity_values['default_revision'] === TRUE) {
          $this->assertRaw($expected_entity_values[$entity_keys['label']]);
        }
        // Node 4 will always appear in the 'stage' workspace because it has
        // both an unpublished revision as well as a published one.
        elseif ($workspace_id != 'stage' && $expected_entity_values[$entity_keys['id']] != 4) {
          $this->assertNoRaw($expected_entity_values[$entity_keys['label']]);
        }
      }
    }
  }

  /**
   * Asserts that default revisions are properly swapped in a workspace.
   *
   * @param array $expected_values
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type to check.
   *
   * @internal
   */
  protected function assertEntityLoad(array $expected_values, string $entity_type_id): void {
    // Filter the expected values so we can check only the default revisions.
    $expected_default_revisions = array_filter($expected_values, function ($expected_value) {
      return $expected_value['default_revision'] === TRUE;
    });

    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    $id_key = $entity_keys['id'];
    $revision_key = $entity_keys['revision'];
    $label_key = $entity_keys['label'];
    $published_key = $entity_keys['published'];

    // Check \Drupal\Core\Entity\EntityStorageInterface::loadMultiple().
    /** @var \Drupal\Core\Entity\RevisionableInterface[]|\Drupal\Core\Entity\EntityPublishedInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple(array_column($expected_default_revisions, $id_key));
    foreach ($expected_default_revisions as $expected_default_revision) {
      $entity_id = $expected_default_revision[$id_key];
      $this->assertEquals($expected_default_revision[$revision_key], $entities[$entity_id]->getRevisionId());
      $this->assertEquals($expected_default_revision[$label_key], $entities[$entity_id]->label());
      $this->assertEquals($expected_default_revision[$published_key], $entities[$entity_id]->isPublished());
    }

    // Check loading entities one by one. It is important to do these checks
    // after the "multiple load" ones above so we can test with a fully warmed
    // static cache.
    foreach ($expected_default_revisions as $expected_default_revision) {
      $entity_id = $expected_default_revision[$id_key];
      $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple([$entity_id]);
      $this->assertCount(1, $entities);
      $this->assertEquals($expected_default_revision[$revision_key], $entities[$entity_id]->getRevisionId());
      $this->assertEquals($expected_default_revision[$label_key], $entities[$entity_id]->label());
      $this->assertEquals($expected_default_revision[$published_key], $entities[$entity_id]->isPublished());
    }

    // Check \Drupal\Core\Entity\EntityStorageInterface::loadUnchanged().
    foreach ($expected_default_revisions as $expected_default_revision) {
      /** @var \Drupal\Core\Entity\RevisionableInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->loadUnchanged($expected_default_revision[$id_key]);
      $this->assertEquals($expected_default_revision[$revision_key], $entity->getRevisionId());
      $this->assertEquals($expected_default_revision[$label_key], $entity->label());
      $this->assertEquals($expected_default_revision[$published_key], $entity->isPublished());
    }
  }

  /**
   * Asserts that non-default revisions are not changed.
   *
   * @param array $expected_values
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type to check.
   *
   * @internal
   */
  protected function assertEntityRevisionLoad(array $expected_values, string $entity_type_id): void {
    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    $id_key = $entity_keys['id'];
    $revision_key = $entity_keys['revision'];
    $label_key = $entity_keys['label'];
    $published_key = $entity_keys['published'];

    /** @var \Drupal\Core\Entity\RevisionableInterface[]|\Drupal\Core\Entity\EntityPublishedInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultipleRevisions(array_column($expected_values, $revision_key));
    foreach ($expected_values as $expected_revision) {
      $revision_id = $expected_revision[$revision_key];
      $this->assertEquals($expected_revision[$id_key], $entities[$revision_id]->id());
      $this->assertEquals($expected_revision[$revision_key], $entities[$revision_id]->getRevisionId());
      $this->assertEquals($expected_revision[$label_key], $entities[$revision_id]->label());
      $this->assertEquals($expected_revision[$published_key], $entities[$revision_id]->isPublished());
    }
  }

  /**
   * Asserts that entity queries are giving the correct results in a workspace.
   *
   * @param array $expected_values
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type to check.
   *
   * @internal
   */
  protected function assertEntityQuery(array $expected_values, string $entity_type_id): void {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    $id_key = $entity_keys['id'];
    $revision_key = $entity_keys['revision'];
    $label_key = $entity_keys['label'];
    $published_key = $entity_keys['published'];

    // Filter the expected values so we can check only the default revisions.
    $expected_default_revisions = array_filter($expected_values, function ($expected_value) {
      return $expected_value['default_revision'] === TRUE;
    });

    // Check entity query counts.
    $result = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();
    $this->assertSame(count($expected_default_revisions), $result);

    $result = (int) $storage->getAggregateQuery()->accessCheck(FALSE)->count()->execute();
    $this->assertSame(count($expected_default_revisions), $result);

    // Check entity queries with no conditions.
    $result = $storage->getQuery()->accessCheck(FALSE)->execute();
    $expected_result = array_combine(array_column($expected_default_revisions, $revision_key), array_column($expected_default_revisions, $id_key));
    $this->assertEquals($expected_result, $result);

    // Check latest revision queries.
    $result = $storage->getQuery()->accessCheck(FALSE)->latestRevision()->execute();
    $this->assertEquals($expected_result, $result);

    // Check querying each revision individually.
    foreach ($expected_values as $expected_value) {
      $query = $storage->getQuery()->accessCheck(FALSE);
      $query
        ->condition($entity_keys['id'], $expected_value[$id_key])
        ->condition($entity_keys['label'], $expected_value[$label_key])
        ->condition($entity_keys['published'], (int) $expected_value[$published_key]);

      // If the entity is not expected to be the default revision, we need to
      // query all revisions if we want to find it.
      if (!$expected_value['default_revision']) {
        $query->allRevisions();
      }

      $result = $query->execute();
      $this->assertEquals([$expected_value[$revision_key] => $expected_value[$id_key]], $result);
    }
  }

  /**
   * Flattens the expectations array defined by testWorkspaces().
   *
   * @param array $expected
   *   An array as defined by testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type that is being tested.
   *
   * @return array
   *   An array where all the entity IDs and revision IDs are merged inside each
   *   expected values array.
   */
  protected function flattenExpectedValues(array $expected, $entity_type_id): array {
    $flattened = [];

    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    foreach ($expected as $workspace_id => $workspace_values) {
      foreach ($workspace_values as $entity_id => $entity_revisions) {
        foreach ($entity_revisions as $revision_id => $revision_values) {
          $flattened[$workspace_id][] = [$entity_keys['id'] => $entity_id, $entity_keys['revision'] => $revision_id] + $revision_values;
        }
      }
    }

    return $flattened;
  }

  /**
   * Tests that entity forms can be stored in the form cache.
   */
  public function testFormCacheForEntityForms(): void {
    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');

    $form_builder = $this->container->get('form_builder');

    $form = $this->entityTypeManager->getFormObject('entity_test_mulrevpub', 'default');
    $form->setEntity(EntityTestMulRevPub::create([]));

    $form_state = new FormState();
    $built_form = $form_builder->buildForm($form, $form_state);
    $form_builder->setCache($built_form['#build_id'], $built_form, $form_state);
  }

  /**
   * Tests that non-entity forms can be stored in the form cache.
   */
  public function testFormCacheForRegularForms(): void {
    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');

    $form_builder = $this->container->get('form_builder');

    $form_state = new FormState();
    $built_form = $form_builder->getForm(FormTestAlterForm::class, $form_state);
    $form_builder->setCache($built_form['#build_id'], $built_form, $form_state);
  }

  /**
   * Tests publishing with fields in dedicated table storage.
   */
  public function testPublishWorkspaceDedicatedTableStorage(): void {
    $this->initializeWorkspacesModule();
    $node_storage = $this->entityTypeManager->getStorage('node');

    $this->workspaceManager->switchToLive();
    $node = $node_storage->create([
      'title' => 'Foo title',
      // Use the body field on node as a test case because it requires dedicated
      // table storage.
      'body' => 'Foo body',
      'type' => 'page',
    ]);
    $node->save();

    $this->switchToWorkspace('stage');
    $node->title = 'Bar title';
    $node->body = 'Bar body';
    $node->save();

    $this->workspaces['stage']->publish();
    $this->workspaceManager->switchToLive();

    $reloaded = $node_storage->load($node->id());
    $this->assertEquals('Bar title', $reloaded->title->value);
    $this->assertEquals('Bar body', $reloaded->body->value);
  }

  /**
   * Tests workspace publishing is not sensitive to node access.
   *
   * The node_access_test module makes anonymous nodes unviewable,
   * so enable it and test getDifferringRevisionIdsOnTarget() with an anonymous
   * node.
   */
  public function testNodeAccessDifferringRevisionIdsOnTarget(): void {
    $this->initializeWorkspacesModule();
    \Drupal::service('module_installer')->install(['node_access_test']);
    node_access_rebuild();

    // Edit node 1 in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('stage - 1 - r3 - unpublished');
    $node->save();

    // Edit node 1 in 'live', and ensure it's anonymous.
    $this->switchToWorkspace('live');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('live - 1 - r4 - unpublished');
    $node->set('uid', 0);
    $node->save();

    /** @var \Drupal\workspaces\WorkspacePublisher $workspace_publisher */
    $workspace_publisher = \Drupal::service('workspaces.operation_factory')->getPublisher($this->workspaces['stage']);

    // Check which revisions are tracked on stage but differ on target.
    $expected = [
      'node' => [
        4 => 1,
      ],
    ];
    $this->assertEquals($expected, $workspace_publisher->getDifferringRevisionIdsOnTarget());

    // Check that there are no more revisions to push after publishing.
    $this->workspaces['stage']->publish();
    $this->assertEmpty($workspace_publisher->getDifferringRevisionIdsOnTarget());
  }

}
