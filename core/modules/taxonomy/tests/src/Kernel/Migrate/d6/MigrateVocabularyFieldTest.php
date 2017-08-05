<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateTaxonomy();
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyField() {
    // Test that the field exists.
    $field_storage_id = 'node.field_tags';
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertSame($field_storage_id, $field_storage->id());

    $settings = $field_storage->getSettings();
    $this->assertSame('taxonomy_term', $settings['target_type'], "Target type is correct.");
    $this->assertSame(1, $field_storage->getCardinality(), "Field cardinality in 1.");

    $this->assertSame(['node', 'field_tags'], $this->getMigration('d6_vocabulary_field')->getIdMap()->lookupDestinationID([4]), "Test IdMap");

    // Tests that a vocabulary named like a D8 base field will be migrated and
    // prefixed with 'field_' to avoid conflicts.
    $field_type = FieldStorageConfig::load('node.field_type');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_type);
  }

}
