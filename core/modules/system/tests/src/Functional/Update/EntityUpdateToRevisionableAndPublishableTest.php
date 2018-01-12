<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\system\Tests\Entity\EntityDefinitionTestTrait;

/**
 * Tests the upgrade path for making an entity revisionable and publishable.
 *
 * @see https://www.drupal.org/node/2841291
 *
 * @group Update
 */
class EntityUpdateToRevisionableAndPublishableTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;
  use DbUpdatesTrait;

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
      __DIR__ . '/../../../fixtures/update/drupal-8.entity-test-schema-converter-enabled.php',
    ];
  }

  /**
   * Tests the conversion of an entity type to revisionable and publishable.
   *
   * @see entity_test_update_update_8400()
   */
  public function testConvertToRevisionableAndPublishable() {
    // Check that entity type is not revisionable nor publishable prior to
    // running the update process.
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertFalse($entity_test_update->isRevisionable());
    $this->assertFalse($entity_test_update->getKey('published'));

    // Make the entity type revisionable, translatable and publishable.
    $this->updateEntityTypeDefinition();

    $this->enableUpdates('entity_test_update', 'entity_rev_pub_updates', 8400);
    $this->runUpdates();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_test_update */
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertTrue($entity_test_update->isRevisionable());
    $this->assertEqual('status', $entity_test_update->getKey('published'));

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $this->assertEqual(count($storage->loadMultiple()), 102, 'All test entities were found.');

    // The conversion to revisionable is already tested by
    // \Drupal\system\Tests\Entity\Update\SqlContentEntityStorageSchemaConverterTest::testMakeRevisionable()
    // so we only need to check that some special cases are handled.
    // All the checks implemented here are taking into consideration the special
    // conditions in which the test database was created.
    // @see _entity_test_update_create_test_entities()

    // The test entity with ID 50 was created before Content Translation was
    // enabled, which means it didn't have a 'content_translation_status' field.
    // content_translation_update_8400() added values for that field which
    // should now be reflected in the entity's 'status' field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    $revision = $storage->loadRevision(50);
    $this->assertEqual(1, $revision->status->value);

    $translation = $revision->getTranslation('ro');
    $this->assertEqual(1, $translation->status->value);

    // The test entity with ID 100 was created with Content Translation enabled
    // and it should have the same values as entity 50.
    $revision = $storage->loadRevision(100);
    $this->assertEqual(1, $revision->status->value);

    $translation = $revision->getTranslation('ro');
    $this->assertEqual(1, $translation->status->value);

    // The test entity 101 had 'content_translation_status' set to 0 for the
    // English (source) language.
    $revision = $storage->loadRevision(101);
    $this->assertEqual(0, $revision->status->value);

    $translation = $revision->getTranslation('ro');
    $this->assertEqual(1, $translation->status->value);

    // The test entity 102 had 'content_translation_status' set to 0 for the
    // Romanian language.
    $revision = $storage->loadRevision(102);
    $this->assertEqual(1, $revision->status->value);

    $translation = $revision->getTranslation('ro');
    $this->assertEqual(0, $translation->status->value);
  }

  /**
   * Updates the 'entity_test_update' entity type to revisionable,
   * translatable, publishable and adds revision metadata keys.
   */
  protected function updateEntityTypeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');

    $keys = $entity_type->getKeys();
    $keys['revision'] = 'revision_id';
    $keys['published'] = 'status';
    $entity_type->set('entity_keys', $keys);

    $revision_metadata_keys = [
      'revision_user' => 'revision_user',
      'revision_created' => 'revision_created',
      'revision_log_message' => 'revision_log_message',
      'revision_default' => 'revision_default',
    ];
    $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

    $entity_type->set('translatable', TRUE);
    $entity_type->set('data_table', 'entity_test_update_data');
    $entity_type->set('revision_table', 'entity_test_update_revision');
    $entity_type->set('revision_data_table', 'entity_test_update_revision_data');

    $this->state->set('entity_test_update.entity_type', $entity_type);

    // Also add the status and revision metadata base fields to the entity type.
    $status = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating the published state.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE);

    $revision_created = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $revision_user = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $revision_log_message = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $this->state->set('entity_test_update.additional_base_field_definitions', [
      'status' => $status,
      'revision_created' => $revision_created,
      'revision_user' => $revision_user,
      'revision_log_message' => $revision_log_message,
    ]);

    $this->entityTypeManager->clearCachedDefinitions();
  }

}
