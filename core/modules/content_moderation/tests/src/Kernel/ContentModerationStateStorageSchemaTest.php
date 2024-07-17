<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test the ContentModerationState storage schema.
 *
 * @coversDefaultClass \Drupal\content_moderation\ContentModerationStateStorageSchema
 * @group content_moderation
 */
class ContentModerationStateStorageSchemaTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'text',
    'workflows',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ])->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();
  }

  /**
   * Tests the ContentModerationState unique keys.
   *
   * @covers ::getEntitySchema
   */
  public function testUniqueKeys(): void {
    // Create a node which will create a new ContentModerationState entity.
    $node = Node::create([
      'title' => 'Test title',
      'type' => 'example',
      'moderation_state' => 'draft',
    ]);
    $node->save();

    // Ensure an exception when all values match.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
    ], TRUE);

    // No exception for the same values, with a different langcode.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
      'langcode' => 'de',
    ], FALSE);

    // A different workflow should not trigger an exception.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
      'workflow' => 'foo',
    ], FALSE);

    // Different entity types should not trigger an exception.
    $this->assertStorageException([
      'content_entity_type_id' => 'entity_test',
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
    ], FALSE);

    // Different entity and revision IDs should not trigger an exception.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => 9999,
      'content_entity_revision_id' => 9999,
    ], FALSE);

    // Creating a version of the entity with a previously used, but not current
    // revision ID should trigger an exception.
    $old_revision_id = $node->getRevisionId();
    $node->setNewRevision(TRUE);
    $node->title = 'Updated title';
    $node->moderation_state = 'published';
    $node->save();
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $old_revision_id,
    ], TRUE);
  }

  /**
   * Assert if a storage exception is triggered when saving a given entity.
   *
   * @param array $values
   *   An array of entity values.
   * @param bool $has_exception
   *   If an exception should be triggered when saving the entity.
   *
   * @internal
   */
  protected function assertStorageException(array $values, bool $has_exception): void {
    $defaults = [
      'moderation_state' => 'draft',
      'workflow' => 'editorial',
    ];
    $entity = ContentModerationState::create($values + $defaults);
    $exception_triggered = FALSE;
    try {
      ContentModerationState::updateOrCreateFromEntity($entity);
    }
    catch (\Exception) {
      $exception_triggered = TRUE;
    }
    $this->assertEquals($has_exception, $exception_triggered);
  }

}
