<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workspaces\Kernel\WorkspaceTestTrait;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;
use Drupal\workspaces\WorkspacePublishException;

/**
 * Tests that Workspaces and Content Moderation work together properly.
 *
 * @group content_moderation
 * @group workspaces
 * @group #slow
 */
class WorkspacesContentModerationStateTest extends ContentModerationStateTest {

  use ContentModerationTestTrait {
    createEditorialWorkflow as traitCreateEditorialWorkflow;
    addEntityTypeAndBundleToWorkflow as traitAddEntityTypeAndBundleToWorkflow;
  }
  use ContentTypeCreationTrait {
    createContentType as traitCreateContentType;
  }
  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * The ID of the revisionable entity type used in the tests.
   *
   * @var string
   */
  protected $revEntityTypeId = 'entity_test_revpub';

  const SKIP_METHODS = [
    // This test creates published default revisions in Live, which can not be
    // deleted in a workspace. A test scenario for the case when Content
    // Moderation and Workspaces are used together is covered in
    // parent::testContentModerationStateRevisionDataRemoval().
    'testContentModerationStateDataRemoval',
    // This test does not assert anything that can be workspace-specific.
    'testModerationWithFieldConfigOverride',
    // This test does not assert anything that can be workspace-specific.
    'testWorkflowDependencies',
    // This test does not assert anything that can be workspace-specific.
    'testWorkflowNonConfigBundleDependencies',
    // This test does not assert anything that can be workspace-specific.
    'testGetCurrentUserId',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (in_array($this->name(), static::SKIP_METHODS, TRUE)) {
      $this->markTestSkipped('Irrelevant for this test');
    }

    parent::setUp();

    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');
  }

  /**
   * Tests that the 'workspace' entity type can not be moderated.
   *
   * @see \Drupal\workspaces\EntityTypeInfo::entityTypeAlter()
   */
  public function testWorkspaceEntityTypeModeration(): void {
    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $entity_type = \Drupal::entityTypeManager()->getDefinition('workspace');
    $this->assertFalse($moderation_info->canModerateEntitiesOfEntityType($entity_type));
  }

  /**
   * Tests the integration between Content Moderation and Workspaces.
   */
  public function testContentModerationIntegrationWithWorkspaces(): void {
    $editorial = $this->createEditorialWorkflow();
    $access_handler = \Drupal::entityTypeManager()->getAccessControlHandler('workspace');

    // Create another workflow which has the same states as the 'editorial' one,
    // but it doesn't create default revisions for the 'archived' state. This
    // covers the case when two bundles of the same entity type use different
    // workflows with same moderation state names but with different settings.
    $editorial_2_values = $editorial->toArray();
    unset($editorial_2_values['uuid']);
    $editorial_2_values['id'] = 'editorial_2';
    $editorial_2_values['type_settings']['states']['archived']['default_revision'] = FALSE;

    $editorial_2 = Workflow::create($editorial_2_values);
    $this->workspaceManager->executeOutsideWorkspace(function () use ($editorial_2) {
      $editorial_2->save();
    });

    // Create two bundles and assign the two workflows for each of them.
    $this->createContentType(['type' => 'page']);
    $this->addEntityTypeAndBundleToWorkflow($editorial, 'node', 'page');
    $this->createContentType(['type' => 'article']);
    $this->addEntityTypeAndBundleToWorkflow($editorial_2, 'node', 'article');

    // Create three entities for each bundle, covering all the available
    // moderation states.
    $page_archived = Node::create([
      'type' => 'page',
      'title' => 'Test page - archived',
      'moderation_state' => 'archived',
    ]);
    $page_archived->save();
    $page_draft = Node::create([
      'type' => 'page',
      'title' => 'Test page - draft',
      'moderation_state' => 'draft',
    ]);
    $page_draft->save();
    $page_published = Node::create([
      'type' => 'page',
      'title' => 'Test page - published',
      'moderation_state' => 'published',
    ]);
    $page_published->save();

    $article_archived = Node::create([
      'type' => 'article',
      'title' => 'Test article - archived',
      'moderation_state' => 'archived',
    ]);
    $article_archived->save();
    $article_draft = Node::create([
      'type' => 'article',
      'title' => 'Test article - draft',
      'moderation_state' => 'draft',
    ]);
    $article_draft->save();
    $article_published = Node::create([
      'type' => 'article',
      'title' => 'Test article - published',
      'moderation_state' => 'published',
    ]);
    $article_published->save();

    // We have three items in a non-default moderation state:
    // - $page_draft
    // - $article_archived
    // - $article_draft
    // Therefore the workspace can not be published.
    // This assertion also covers two moderation states from different workflows
    // with the same name ('archived'), but with different default revision
    // settings.
    try {
      $this->workspaces['stage']->publish();
      $this->fail('The expected exception was not thrown.');
    }
    catch (WorkspacePublishException $e) {
      $this->assertEquals('The Stage workspace can not be published because it contains 3 items in an unpublished moderation state.', $e->getMessage());
    }

    // Get the $page_draft node to a publishable state and try again.
    $page_draft->moderation_state->value = 'published';
    $page_draft->save();
    try {
      $access_handler->resetCache();
      $this->workspaces['stage']->publish();
      $this->fail('The expected exception was not thrown.');
    }
    catch (WorkspacePublishException $e) {
      $this->assertEquals('The Stage workspace can not be published because it contains 2 items in an unpublished moderation state.', $e->getMessage());
    }

    // Get the $article_archived node to a publishable state and try again.
    $article_archived->moderation_state->value = 'published';
    $article_archived->save();
    try {
      $access_handler->resetCache();
      $this->workspaces['stage']->publish();
      $this->fail('The expected exception was not thrown.');
    }
    catch (WorkspacePublishException $e) {
      $this->assertEquals('The Stage workspace can not be published because it contains 1 item in an unpublished moderation state.', $e->getMessage());
    }

    // Get the $article_draft node to a publishable state and try again.
    $article_draft->moderation_state->value = 'published';
    $article_draft->save();
    $access_handler->resetCache();
    $this->workspaces['stage']->publish();
  }

  /**
   * Publish a workspace with workflows including no tracked default revisions.
   */
  public function testContentModerationWithoutDefaultRevisionsInWorkspaces(): void {
    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('workspace');
    // Create a workflow which has the same states as the 'editorial' one,
    // but it doesn't create any default revisions. This covers the case when a
    // workspace is published containing no tracked types. This has to be the
    // only workflow.
    $editorial = $this->createEditorialWorkflow();
    $type_settings = $editorial->get('type_settings');
    $type_settings['states']['draft']['default_revision'] = FALSE;
    $type_settings['states']['archived']['default_revision'] = FALSE;
    $this->workspaceManager->executeOutsideWorkspace(function () use ($editorial) {
      $editorial->save();
    });
    // Create an node bundle 'note' that uses non-default workflow.
    $this->createContentType(['type' => 'note']);

    // Create content in all states none with default revisions.
    $note_archived = Node::create([
      'type' => 'note',
      'title' => 'Test note - archived',
      'moderation_state' => 'archived',
    ]);
    $note_archived->save();
    $note_draft = Node::create([
      'type' => 'note',
      'title' => 'Test note - draft',
      'moderation_state' => 'draft',
    ]);
    $note_draft->save();
    $note_published = Node::create([
      'type' => 'note',
      'title' => 'Test note - published',
      'moderation_state' => 'published',
    ]);
    $note_published->save();

    // Check workspace can be published.
    $access_handler->resetCache();
    $this->workspaces['stage']->publish();
  }

  /**
   * Publish a workspace with multiple entities from different entity types.
   */
  public function testContentModerationMultipleEntityTypesWithWorkspaces(): void {
    $editorial = $this->createEditorialWorkflow();
    $this->createContentType(['type' => 'page']);
    $this->addEntityTypeAndBundleToWorkflow($editorial, 'node', 'page');
    $this->addEntityTypeAndBundleToWorkflow($editorial, 'entity_test_mulrevpub', 'entity_test_mulrevpub');

    // Create an entity with a previous revision that is tracked in unpublished
    // state.
    $entity_with_revision = EntityTestMulRevPub::create([
      'title' => 'Test entity mulrevpub',
      'type' => 'entity_test_mulrevpub',
      'moderation_state' => 'draft',
    ]);
    $entity_with_revision->save();
    $entity_with_revision->save();
    $entity_with_revision = $this->reloadEntity($entity_with_revision);
    // Confirm unpublished earlier revision.
    $this->assertEquals('draft', $entity_with_revision->moderation_state->value);
    $earlier_revision_id = $entity_with_revision->getRevisionId();
    // Publish.
    $entity_with_revision->moderation_state->value = 'published';
    $entity_with_revision->save();
    $entity_with_revision = $this->reloadEntity($entity_with_revision);
    // Confirm publish revision.
    $this->assertEquals('published', $entity_with_revision->moderation_state->value);
    $published_revision_id = $entity_with_revision->getRevisionId();
    $this->assertNotEquals($earlier_revision_id, $published_revision_id);

    // Create an entity that has a default revision id the same as the previous
    // entity's old revision.
    $entity_without_revision = Node::create([
      'title' => 'Test node page',
      'type' => 'page',
      'moderation_state' => 'published',
    ]);
    $entity_without_revision->save();
    $entity_without_revision = $this->reloadEntity($entity_without_revision);
    $this->assertEquals('published', $entity_without_revision->moderation_state->value);

    // Current published revisions of second entity has the same revision as
    // earlier unpublished revision of first entity.
    $this->assertEquals($entity_without_revision->getRevisionId(), $earlier_revision_id);
    $this->workspaces['stage']->publish();
  }

  /**
   * Test cases for basic moderation test.
   */
  public static function basicModerationTestCases() {
    return [
      'Nodes' => [
        'node',
      ],
      'Block content' => [
        'block_content',
      ],
      'Media' => [
        'media',
      ],
      'Test entity - revisions, data table, and published interface' => [
        'entity_test_mulrevpub',
      ],
      'Entity Test with revisions and published status' => [
        'entity_test_revpub',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($entity_type_id, $moderation_state = 'published', $create_workflow = TRUE) {
    $entity = $this->workspaceManager->executeOutsideWorkspace(function () use ($entity_type_id, $moderation_state, $create_workflow) {
      return parent::createEntity($entity_type_id, $moderation_state, $create_workflow);
    });

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEditorialWorkflow() {
    $workflow = $this->workspaceManager->executeOutsideWorkspace(function () {
      return $this->traitCreateEditorialWorkflow();
    });

    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  protected function addEntityTypeAndBundleToWorkflow(WorkflowInterface $workflow, $entity_type_id, $bundle): void {
    $this->workspaceManager->executeOutsideWorkspace(function () use ($workflow, $entity_type_id, $bundle) {
      $this->traitAddEntityTypeAndBundleToWorkflow($workflow, $entity_type_id, $bundle);
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function createContentType(array $values = []) {
    $note_type = $this->workspaceManager->executeOutsideWorkspace(function () use ($values) {
      return $this->traitCreateContentType($values);
    });

    return $note_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function assertDefaultRevision(EntityInterface $entity, int $revision_id, $published = TRUE): void {
    // In the context of a workspace, the default revision ID is always the
    // latest workspace-specific revision, so we need to adjust the expectation
    // of the parent assertion.
    $revision_id = (int) $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id())->getRevisionId();

    // Additionally, the publishing status of the default revision is not
    // relevant in a workspace, because getting an entity to a "published"
    // moderation state doesn't automatically make it the default revision, so
    // we have to disable that assertion.
    $published = NULL;

    parent::assertDefaultRevision($entity, $revision_id, $published);
  }

}
