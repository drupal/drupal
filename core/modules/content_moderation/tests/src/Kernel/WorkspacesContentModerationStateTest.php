<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workspaces\Kernel\WorkspaceTestTrait;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;
use Drupal\workspaces\WorkspaceAccessException;

/**
 * Tests that Workspaces and Content Moderation work together properly.
 *
 * @group content_moderation
 * @group workspaces
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    $this->initializeWorkspacesModule();
    $this->switchToWorkspace('stage');
  }

  /**
   * Tests that the 'workspace' entity type can not be moderated.
   *
   * @see \Drupal\workspaces\EntityTypeInfo::entityTypeAlter()
   */
  public function testWorkspaceEntityTypeModeration() {
    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $entity_type = \Drupal::entityTypeManager()->getDefinition('workspace');
    $this->assertFalse($moderation_info->canModerateEntitiesOfEntityType($entity_type));
  }

  /**
   * Tests the integration between Content Moderation and Workspaces.
   *
   * @see content_moderation_workspace_access()
   */
  public function testContentModerationIntegrationWithWorkspaces() {
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
    $page_archived = Node::create(['type' => 'page', 'title' => 'Test page - archived', 'moderation_state' => 'archived']);
    $page_archived->save();
    $page_draft = Node::create(['type' => 'page', 'title' => 'Test page - draft', 'moderation_state' => 'draft']);
    $page_draft->save();
    $page_published = Node::create(['type' => 'page', 'title' => 'Test page - published', 'moderation_state' => 'published']);
    $page_published->save();

    $article_archived = Node::create(['type' => 'article', 'title' => 'Test article - archived', 'moderation_state' => 'archived']);
    $article_archived->save();
    $article_draft = Node::create(['type' => 'article', 'title' => 'Test article - draft', 'moderation_state' => 'draft']);
    $article_draft->save();
    $article_published = Node::create(['type' => 'article', 'title' => 'Test article - published', 'moderation_state' => 'published']);
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
    catch (WorkspaceAccessException $e) {
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
    catch (WorkspaceAccessException $e) {
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
    catch (WorkspaceAccessException $e) {
      $this->assertEquals('The Stage workspace can not be published because it contains 1 item in an unpublished moderation state.', $e->getMessage());
    }

    // Get the $article_draft node to a publishable state and try again.
    $article_draft->moderation_state->value = 'published';
    $article_draft->save();
    $access_handler->resetCache();
    $this->workspaces['stage']->publish();
  }

  /**
   * Test cases for basic moderation test.
   */
  public function basicModerationTestCases() {
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
  public function testModerationWithFieldConfigOverride() {
    // This test does not assert anything that can be workspace-specific.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testWorkflowDependencies() {
    // This test does not assert anything that can be workspace-specific.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testWorkflowNonConfigBundleDependencies() {
    // This test does not assert anything that can be workspace-specific.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testGetCurrentUserId() {
    // This test does not assert anything that can be workspace-specific.
    $this->markTestSkipped();
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
  protected function addEntityTypeAndBundleToWorkflow(WorkflowInterface $workflow, $entity_type_id, $bundle) {
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
  protected function assertDefaultRevision(EntityInterface $entity, $revision_id, $published = TRUE) {
    // In the context of a workspace, the default revision ID is always the
    // latest workspace-specific revision, so we need to adjust the expectation
    // of the parent assertion.
    $revision_id = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id())->getRevisionId();

    // Additionally, the publishing status of the default revision is not
    // relevant in a workspace, because getting an entity to a "published"
    // moderation state doesn't automatically make it the default revision, so
    // we have to disable that assertion.
    $published = NULL;

    parent::assertDefaultRevision($entity, $revision_id, $published);
  }

}
