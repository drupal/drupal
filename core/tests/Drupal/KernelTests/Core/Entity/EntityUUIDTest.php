<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * Tests creation, saving, and loading of entity UUIDs.
 *
 * @group Entity
 */
class EntityUUIDTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    foreach (entity_test_entity_types() as $entity_type_id) {
      // The entity_test schema is installed by the parent.
      if ($entity_type_id != 'entity_test') {
        $this->installEntitySchema($entity_type_id);
      }
    }
  }

  /**
   * Tests UUID generation in entity CRUD operations.
   */
  public function testCRUD() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertCRUD($entity_type);
    }
  }

  /**
   * Executes the UUID CRUD tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   *
   * @internal
   */
  protected function assertCRUD(string $entity_type): void {
    // Verify that no UUID is auto-generated when passing one for creation.
    $uuid_service = $this->container->get('uuid');
    $uuid = $uuid_service->generate();
    $custom_entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => $this->randomMachineName(),
        'uuid' => $uuid,
      ]);
    $this->assertSame($uuid, $custom_entity->uuid());
    // Save this entity, so we have more than one later.
    $custom_entity->save();

    // Verify that a new UUID is generated upon creating an entity.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['name' => $this->randomMachineName()]);
    $uuid = $entity->uuid();
    $this->assertNotEmpty($uuid);

    // Verify that the new UUID is different.
    $this->assertNotEquals($custom_entity->uuid(), $uuid);

    // Verify that the UUID is retained upon saving.
    $entity->save();
    $this->assertSame($uuid, $entity->uuid());

    // Verify that the UUID is retained upon loading.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $storage->resetCache([$entity->id()]);
    $entity_loaded = $storage->load($entity->id());
    $this->assertSame($uuid, $entity_loaded->uuid());

    // Verify that \Drupal::service('entity.repository')->loadEntityByUuid() loads the same entity.
    $entity_loaded_by_uuid = \Drupal::service('entity.repository')->loadEntityByUuid($entity_type, $uuid, TRUE);
    $this->assertSame($uuid, $entity_loaded_by_uuid->uuid());
    $this->assertEquals($entity_loaded->id(), $entity_loaded_by_uuid->id());

    // Creating a duplicate needs to result in a new UUID.
    $entity_duplicate = $entity->createDuplicate();
    foreach ($entity->getFields() as $property => $value) {
      switch ($property) {
        case 'uuid':
          $this->assertNotNull($entity_duplicate->uuid());
          $this->assertNotNull($entity->uuid());
          $this->assertNotEquals($entity->uuid(), $entity_duplicate->uuid());
          break;

        case 'id':
          $this->assertNull($entity_duplicate->id());
          $this->assertNotNull($entity->id());
          $this->assertNotEquals($entity->id(), $entity_duplicate->id());
          break;

        case 'revision_id':
          $this->assertNull($entity_duplicate->getRevisionId());
          $this->assertNotNull($entity->getRevisionId());
          $this->assertNotEquals($entity->getRevisionId(), $entity_duplicate->getRevisionId());
          $this->assertNotEquals($entity->{$property}->getValue(), $entity_duplicate->{$property}->getValue());
          break;

        default:
          $this->assertEquals($entity->{$property}->getValue(), $entity_duplicate->{$property}->getValue());
      }
    }
    $entity_duplicate->save();
    $this->assertNotEquals($entity_duplicate->id(), $entity->id());
  }

}
