<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests Content Moderation with entities that get re-saved automatically.
 *
 * @group content_moderation
 */
class ContentModerationResaveTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Make sure the test module is listed first as module weights do not apply
    // for kernel tests.
    /* @see \content_moderation_test_resave_install() */
    'content_moderation_test_resave',
    'content_moderation',
    'entity_test',
    'user',
    'workflows',
  ];

  /**
   * The content moderation state entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contentModerationStateStorage;

  /**
   * The entity storage for the entity type used in the test.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_id = 'entity_test_rev';

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema($entity_type_id);

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, $entity_type_id, $entity_type_id);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->contentModerationStateStorage = $entity_type_manager->getStorage('content_moderation_state');
    $this->entityStorage = $entity_type_manager->getStorage($entity_type_id);
    $this->state = $this->container->get('state');
  }

  /**
   * Tests that Content Moderation works with entities being resaved.
   */
  public function testContentModerationResave() {
    $entity = $this->entityStorage->create();
    $this->assertSame('draft', $entity->get('moderation_state')->value);
    $this->assertNull(\Drupal::state()->get('content_moderation_test_resave'));
    $this->assertNull(ContentModerationState::loadFromModeratedEntity($entity));
    $content_moderation_state_query = $this->contentModerationStateStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->count();
    $this->assertSame(0, (int) $content_moderation_state_query->execute());
    $content_moderation_state_revision_query = $this->contentModerationStateStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->count();
    $this->assertSame(0, (int) $content_moderation_state_revision_query->execute());

    // The test module will re-save the entity in its hook_insert()
    // implementation creating the content moderation state entity before
    // Content Moderation's hook_insert() has run for the initial save
    // operation.
    $entity->save();
    $this->assertSame('draft', $entity->get('moderation_state')->value);
    $this->assertTrue(\Drupal::state()->get('content_moderation_test_resave'));
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($entity);
    $this->assertInstanceOf(ContentModerationState::class, $content_moderation_state);
    $this->assertSame(1, (int) $content_moderation_state_query->execute());
    $this->assertSame(1, (int) $content_moderation_state_revision_query->execute());
  }

}
