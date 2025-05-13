<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityDefinitionUpdateManager
 *
 * @group Entity
 */
class EntityDefinitionUpdateTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests that new entity type definitions are correctly handled.
   */
  public function testNewEntityType(): void {
    $entity_type_id = 'entity_test_new';
    $schema = $this->database->schema();

    // Check that the "entity_test_new" is not defined.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->assertFalse(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type does not exist.');
    $this->assertFalse($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type does not exist.');

    // Check that the "entity_test_new" is now defined and the related schema
    // has been created.
    $this->enableNewEntityType();
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->assertTrue(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type exists.');
    $this->assertTrue($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type has been created.');
  }

  /**
   * Tests installing an additional base field while installing an entity type.
   *
   * @covers ::installFieldableEntityType
   */
  public function testInstallAdditionalBaseFieldDuringFieldableEntityTypeInstallation(): void {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');

    // Enable the creation of a new base field during the installation of a
    // fieldable entity type.
    $this->state->set('entity_test_update.install_new_base_field_during_create', TRUE);

    // Install the entity type and check that the additional base field was also
    // installed.
    $this->entityDefinitionUpdateManager->installFieldableEntityType($entity_type, $field_storage_definitions);

    // Check whether the 'new_base_field' field has been installed correctly.
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('new_base_field', 'entity_test_update');
    $this->assertNotNull($field_storage_definition);
  }

  /**
   * @covers ::getEntityTypes
   */
  public function testGetEntityTypes(): void {
    $entity_type_definitions = $this->entityDefinitionUpdateManager->getEntityTypes();

    // Ensure that we have at least one entity type to check below.
    $this->assertGreaterThanOrEqual(1, count($entity_type_definitions));

    foreach ($entity_type_definitions as $entity_type_id => $entity_type) {
      $this->assertEquals($this->entityDefinitionUpdateManager->getEntityType($entity_type_id), $entity_type);
    }
  }

}
