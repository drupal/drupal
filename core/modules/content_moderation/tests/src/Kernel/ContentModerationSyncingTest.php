<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test content moderation when an entity is marked as 'syncing'.
 *
 * @group content_moderation
 */
class ContentModerationSyncingTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'workflows',
    'content_moderation',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('workflow');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('entity_test_mulrevpub');

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_mulrevpub', 'entity_test_mulrevpub');
    $workflow->save();
  }

  /**
   * Test no new revision is forced during a sync.
   */
  public function testNoRevisionForcedDuringSync() {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'draft',
      'name' => 'foo',
    ]);
    $entity->save();
    $initial_revision_id = $entity->getRevisionId();

    $entity->setSyncing(TRUE);
    $entity->name = 'bar';
    $entity->save();

    $this->assertEquals($entity->getRevisionId(), $initial_revision_id);
  }

  /**
   * Test changing the moderation state during a sync.
   */
  public function testSingleRevisionStateChangedDuringSync() {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
      'name' => 'foo',
    ]);
    $entity->save();
    $initial_revision_id = $entity->getRevisionId();
    $this->assertTrue($entity->isDefaultRevision());
    $this->assertTrue($entity->isPublished());

    $entity->setSyncing(TRUE);
    $entity->moderation_state = 'draft';
    $entity->save();

    // If a moderation state is changed to a draft while syncing, it will revert
    // to the same properties of an item of content that was initially created
    // as a draft.
    $this->assertEquals($initial_revision_id, $entity->getRevisionId());
    $this->assertFalse($entity->isPublished());
    $this->assertTrue($entity->isDefaultRevision());
    $this->assertEquals('draft', $entity->moderation_state->value);
  }

  /**
   * Test state changes with multiple revisions during a sync.
   */
  public function testMultipleRevisionStateChangedDuringSync() {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
      'name' => 'foo',
    ]);
    $entity->save();

    $entity->name = 'bar';
    $entity->save();
    $latest_revision_id = $entity->getRevisionId();

    $entity->setSyncing(TRUE);
    $entity->moderation_state = 'draft';
    $entity->save();

    $this->assertEquals($latest_revision_id, $entity->getRevisionId());
    $this->assertEquals('draft', $entity->moderation_state->value);
    $this->assertEquals('bar', $entity->name->value);
    // The default revision will not automatically be assigned to another
    // revision, so a draft unpublished revision will be created when syncing
    // 'published' to 'draft'.
    $this->assertFalse($entity->isPublished());
    $this->assertTrue($entity->isDefaultRevision());
  }

  /**
   * Test modifying a previous revision during a sync.
   */
  public function testUpdatingPreviousRevisionDuringSync() {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mulrevpub');

    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
      'name' => 'foo',
    ]);
    $entity->save();
    $original_revision_id = $entity->getRevisionId();

    $entity->name = 'bar';
    $entity->save();

    // Sync a change to the 'name' on the original revision ID.
    $original_revision = $storage->loadRevision($original_revision_id);
    $original_revision->setSyncing(TRUE);
    $original_revision->name = 'baz';
    $original_revision->save();

    // The names of each revision should reflect two revisions, the original one
    // having been updated during a sync.
    $this->assertEquals(['baz', 'bar'], $this->getAllRevisionNames($entity));
  }

  /**
   * Test a moderation state changed on a previous revision during a sync.
   */
  public function testStateChangedPreviousRevisionDuringSync() {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mulrevpub');

    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
      'name' => 'foo',
    ]);
    $entity->save();

    $entity->moderation_state = 'draft';
    $entity->name = 'bar';
    $entity->save();
    $draft_revision_id = $entity->getRevisionId();

    $entity->name = 'baz';
    $entity->moderation_state = 'published';
    $entity->save();
    $default_revision_id = $entity->getRevisionId();

    // Update the draft revision moderation state to published, which would
    // typically change the default status of a revision during moderation.
    $draft_revision = $storage->loadRevision($draft_revision_id);
    $draft_revision->setSyncing(TRUE);
    $draft_revision->name = 'qux';
    $draft_revision->moderation_state = 'published';
    $draft_revision->save();

    // Ensure the default revision is not changed during the sync.
    $reloaded_default_revision = $storage->load($entity->id());
    $this->assertEquals($default_revision_id, $reloaded_default_revision->getRevisionId());
    $this->assertEquals([
      'foo',
      'qux',
      'baz',
    ], $this->getAllRevisionNames($reloaded_default_revision));
  }

  /**
   * Get all the revision names in order of the revision ID.
   *
   * @param \Drupal\entity_test\Entity\EntityTestMulRevPub $entity
   *   The entity.
   *
   * @return array
   *   An array of revision names.
   */
  protected function getAllRevisionNames(EntityTestMulRevPub $entity) {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mulrevpub');
    return array_map(function($revision_id) use ($storage) {
      return $storage->loadRevision($revision_id)->name->value;
    }, array_keys($storage->getQuery()
        ->allRevisions()
        ->condition('id', $entity->id())
        ->sort('revision_id', 'ASC')
        ->execute())
    );
  }

}
