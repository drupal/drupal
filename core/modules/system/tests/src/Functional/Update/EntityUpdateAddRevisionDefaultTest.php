<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the upgrade path for adding the 'revision_default' field.
 *
 * @see https://www.drupal.org/project/drupal/issues/2891215
 *
 * @group Update
 * @group legacy
 */
class EntityUpdateAddRevisionDefaultTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;
  use DbUpdatesTrait;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The last installed schema repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = \Drupal::entityManager();
    $this->lastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');
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
   * Tests the addition of the 'revision_default' base field.
   *
   * @see system_update_8501()
   */
  public function testAddingTheRevisionDefaultField() {
    // Make the entity type revisionable and translatable prior to running the
    // updates.
    $this->updateEntityTypeToRevisionableAndTranslatable();

    // Check that the test entity type does not have the 'revision_default'
    // field before running the updates.
    $field_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertFalse(isset($field_storage_definitions['revision_default']));

    $this->runUpdates();

    // Check that the 'revision_default' field has been added by
    // system_update_8501().
    $field_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertTrue(isset($field_storage_definitions['revision_default']));

    // Check that the correct initial value was set when the field was
    // installed.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityManager->getStorage('entity_test_update')->load(1);
    $this->assertTrue($entity->wasDefaultRevision());
  }

}
