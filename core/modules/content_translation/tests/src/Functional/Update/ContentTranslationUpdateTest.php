<?php

namespace Drupal\Tests\content_translation\Functional\Update;

use Drupal\Core\Language\LanguageInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the upgrade path for the Content Translation module.
 *
 * @group Update
 * @group legacy
 */
class ContentTranslationUpdateTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

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

    $this->database = \Drupal::database();
    $this->entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
    $this->state = \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update_mul.php.gz',
    ];
  }

  /**
   * Tests that initial values for metadata fields are populated correctly.
   */
  public function testContentTranslationUpdate8400() {
    $this->updateEntityTypeToTranslatable();

    // The test database dump contains NULL values for
    // 'content_translation_source', 'content_translation_outdated' and
    // 'content_translation_status' for the first 50 test entities.
    // @see _entity_test_update_create_test_entities()
    $first_entity_record = $this->database->select('entity_test_update_data', 'etud')
      ->fields('etud')
      ->condition('etud.id', 1)
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertNull($first_entity_record[1]->content_translation_source);
    $this->assertNull($first_entity_record[1]->content_translation_outdated);
    $this->assertNull($first_entity_record[1]->content_translation_status);

    $this->runUpdates();

    // After running the updates, all those fields should be populated with
    // their default values.
    $first_entity_record = $this->database->select('entity_test_update_data', 'etud')
      ->fields('etud')
      ->condition('etud.id', 1)
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertEqual(LanguageInterface::LANGCODE_NOT_SPECIFIED, $first_entity_record[1]->content_translation_source);
    $this->assertEqual(0, $first_entity_record[1]->content_translation_outdated);
    $this->assertEqual(1, $first_entity_record[1]->content_translation_status);
  }

}
