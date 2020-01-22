<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the upgrade path for making an entity publishable.
 *
 * @group Update
 * @group legacy
 */
class EntityUpdateToPublishableTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;
  use DbUpdatesTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The last installed schema repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

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

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
    $this->lastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');
    $this->installedStorageSchema = \Drupal::keyValue('entity.storage_schema.sql');
    $this->state = \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update_mul.php.gz',
];
  }

  /**
   * Tests the conversion of an entity type to be publishable.
   *
   * @see entity_test_update_update_8400()
   */
  public function testConvertToPublishable() {
    // Check that entity type is not publishable prior to running the update
    // process.
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertFalse($entity_test_update->getKey('published'));

    // Make the entity type translatable and publishable.
    $this->updateEntityTypeDefinition();

    $this->enableUpdates('entity_test_update', 'entity_rev_pub_updates', 8400);
    $this->runUpdates();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_test_update */
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertEquals('status', $entity_test_update->getKey('published'));

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $this->assertCount(102, $storage->loadMultiple(), 'All test entities were found.');

    // The test entity with ID 50 was created before Content Translation was
    // enabled, which means it didn't have a 'content_translation_status' field.
    // content_translation_update_8400() added values for that field which
    // should now be reflected in the entity's 'status' field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->load(50);
    $this->assertEquals(1, $entity->status->value);

    $translation = $entity->getTranslation('ro');
    $this->assertEquals(1, $translation->status->value);

    // The test entity with ID 100 was created with Content Translation enabled
    // and it should have the same values as entity 50.
    $entity = $storage->load(100);
    $this->assertEquals(1, $entity->status->value);

    $translation = $entity->getTranslation('ro');
    $this->assertEquals(1, $translation->status->value);

    // The test entity 101 had 'content_translation_status' set to 0 for the
    // English (source) language.
    $entity = $storage->load(101);
    $this->assertEquals(0, $entity->status->value);

    $translation = $entity->getTranslation('ro');
    $this->assertEquals(1, $translation->status->value);

    // The test entity 102 had 'content_translation_status' set to 0 for the
    // Romanian language.
    $entity = $storage->load(102);
    $this->assertEquals(1, $entity->status->value);

    $translation = $entity->getTranslation('ro');
    $this->assertEquals(0, $translation->status->value);
  }

  /**
   * Updates the 'entity_test_update' entity type to translatable and
   * publishable.
   */
  protected function updateEntityTypeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');

    $keys = $entity_type->getKeys();
    $keys['published'] = 'status';
    $entity_type->set('entity_keys', $keys);

    $entity_type->set('translatable', TRUE);
    $entity_type->set('data_table', 'entity_test_update_data');

    $this->state->set('entity_test_update.entity_type', $entity_type);

    // Add the status field to the entity type.
    $status = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating the published state.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE);

    $this->state->set('entity_test_update.additional_base_field_definitions', [
      'status' => $status,
    ]);

    $this->entityTypeManager->clearCachedDefinitions();
  }

}
