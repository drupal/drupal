<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests loading entities by UUID.
 *
 * @group entity
 */
class EntityLoadByUuidTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Ensures that ::loadEntityByUuid() doesn't apply access checking.
   */
  public function testLoadEntityByUuidAccessChecking(): void {
    \Drupal::state()->set('entity_test_query_access', TRUE);
    // Create two test entities.
    $entity_0 = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'published entity',
    ]);
    $entity_0->save();
    $entity_1 = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'unpublished entity',
    ]);
    $entity_1->save();

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = \Drupal::service('entity.repository');
    $this->assertEquals($entity_0->id(), $repository->loadEntityByUuid('entity_test', $entity_0->uuid())->id());
    $this->assertEquals($entity_1->id(), $repository->loadEntityByUuid('entity_test', $entity_1->uuid())->id());
  }

}
