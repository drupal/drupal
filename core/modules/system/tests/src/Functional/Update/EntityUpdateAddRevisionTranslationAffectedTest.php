<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the upgrade path for adding the 'revision_translation_affected' field.
 *
 * @see https://www.drupal.org/node/2896845
 *
 * @group Update
 * @group legacy
 */
class EntityUpdateAddRevisionTranslationAffectedTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;
  use DbUpdatesTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Do not use this property after calling ::runUpdates().
    $this->state = \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update_mul_rev.php.gz',
    ];
  }

  /**
   * Tests the addition of the 'revision_translation_affected' base field.
   *
   * @see system_update_8402()
   * @see system_update_8702()
   */
  public function testAddingTheRevisionTranslationAffectedField() {
    // Make the entity type revisionable and translatable prior to running the
    // updates.
    $this->updateEntityTypeToRevisionableAndTranslatable();

    // Check that the test entity type does not have the
    // 'revision_translation_affected' field before running the updates.
    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertFalse(isset($field_storage_definitions['revision_translation_affected']));

    $this->runUpdates();

    // Check that the 'revision_translation_affected' field has been added by
    // system_update_8402().
    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertTrue(isset($field_storage_definitions['revision_translation_affected']));

    // Check that the entity type has the 'revision_translation_affected' key.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('entity_test_update');
    $this->assertEquals('revision_translation_affected', $entity_type->getKey('revision_translation_affected'));

    // Check that the correct initial value was set when the field was
    // installed.
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test_update')->load(1);
    $this->assertNotEmpty($entity->revision_translation_affected->value);
  }

  /**
   * Tests the 'revision_translation_affected' field on a deleted entity type.
   */
  public function testDeletedEntityType() {
    // Delete the entity type before running the update. This tests the case
    // where the code of an entity type has been removed but its definition has
    // not yet been uninstalled.
    $this->deleteEntityType();
    \Drupal::entityTypeManager()->clearCachedDefinitions();

    // Check that the test entity type does not have the
    // 'revision_translation_affected' field before running the updates.
    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertFalse(isset($field_storage_definitions['revision_translation_affected']));

    $this->runUpdates();

    // Check that the 'revision_translation_affected' field has not been added
    // by system_update_8402().
    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertFalse(isset($field_storage_definitions['revision_translation_affected']));

    // Check that the entity type definition has not been updated with the new
    // 'revision_translation_affected' key.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('entity_test_update');
    $this->assertFalse($entity_type->getKey('revision_translation_affected'));
  }

}
