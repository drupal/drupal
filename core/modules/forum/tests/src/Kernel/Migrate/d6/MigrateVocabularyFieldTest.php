<?php

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Vocabulary field migration.
 *
 * @group forum
 */
class MigrateVocabularyFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'forum',
    'taxonomy',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateTaxonomy();
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association migration.
   */
  public function testVocabularyField() {
    // Test that the field exists.
    $this->assertEntity('node.field_freetags', 'entity_reference', TRUE, -1);
    $this->assertEntity('node.field_trees', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.taxonomy_forums', 'entity_reference', TRUE, 1);
  }

  /**
   * Asserts various aspects of a field_storage_config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.FIELD_NAME.
   * @param string $expected_type
   *   The expected field type.
   * @param bool $expected_translatable
   *   Whether or not the field is expected to be translatable.
   * @param int $expected_cardinality
   *   The expected cardinality of the field.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_type, bool $expected_translatable, int $expected_cardinality): void {
    [$expected_entity_type, $expected_name] = explode('.', $id);

    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = FieldStorageConfig::load($id);
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field);
    $this->assertEquals($expected_name, $field->getName());
    $this->assertEquals($expected_type, $field->getType());
    $this->assertEquals($expected_translatable, $field->isTranslatable());
    $this->assertEquals($expected_entity_type, $field->getTargetEntityTypeId());

    if ($expected_cardinality === 1) {
      $this->assertFalse($field->isMultiple());
    }
    else {
      $this->assertTrue($field->isMultiple());
    }
    $this->assertEquals($expected_cardinality, $field->getCardinality());
  }

}
