<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates Drupal 7 fields.
 *
 * @group field
 */
class MigrateFieldTest extends MigrateDrupal7TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'datetime',
    'file',
    'image',
    'link',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_field');
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
   */
  protected function assertEntity($id, $expected_type, $expected_translatable, $expected_cardinality) {
    list ($expected_entity_type, $expected_name) = explode('.', $id);

    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = FieldStorageConfig::load($id);
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $this->assertIdentical($expected_name, $field->getName());
    $this->assertIdentical($expected_type, $field->getType());
    $this->assertEqual($expected_translatable, $field->isTranslatable());
    $this->assertIdentical($expected_entity_type, $field->getTargetEntityTypeId());

    if ($expected_cardinality === 1) {
      $this->assertFalse($field->isMultiple());
    }
    else {
      $this->assertTrue($field->isMultiple());
    }
    $this->assertIdentical($expected_cardinality, $field->getCardinality());
  }

  /**
   * Tests migrating D7 fields to field_storage_config entities.
   */
  public function testFields() {
    $this->assertEntity('node.body', 'text_with_summary', TRUE, 1);
    $this->assertEntity('node.field_long_text', 'text_with_summary', TRUE, 1);
    $this->assertEntity('comment.comment_body', 'text_long', TRUE, 1);
    $this->assertEntity('node.field_file', 'file', TRUE, 1);
    $this->assertEntity('user.field_file', 'file', TRUE, 1);
    $this->assertEntity('node.field_float', 'float', TRUE, 1);
    $this->assertEntity('node.field_image', 'image', TRUE, 1);
    $this->assertEntity('node.field_images', 'image', TRUE, 1);
    $this->assertEntity('node.field_integer', 'integer', TRUE, 1);
    $this->assertEntity('comment.field_integer', 'integer', TRUE, 1);
    $this->assertEntity('node.field_integer_list', 'list_integer', TRUE, 1);
    $this->assertEntity('node.field_link', 'link', TRUE, 1);
    $this->assertEntity('node.field_tags', 'entity_reference', TRUE, -1);
    $this->assertEntity('node.field_term_reference', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.taxonomy_forums', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.field_text', 'text', TRUE, 1);
    $this->assertEntity('node.field_text_list', 'list_string', TRUE, 3);
    $this->assertEntity('node.field_boolean', 'boolean', TRUE, 1);
    $this->assertEntity('node.field_email', 'email', TRUE, -1);
    $this->assertEntity('node.field_phone', 'telephone', TRUE, 1);
    $this->assertEntity('node.field_date', 'datetime', TRUE, 1);
    $this->assertEntity('node.field_date_with_end_time', 'datetime', TRUE, 1);
    $this->assertEntity('node.field_node_entityreference', 'entity_reference', TRUE, -1);
    $this->assertEntity('node.field_user_entityreference', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.field_term_entityreference', 'entity_reference', TRUE, -1);

    // Assert that the taxonomy term reference fields are referencing the
    // correct entity type.
    $field = FieldStorageConfig::load('node.field_term_reference');
    $this->assertIdentical('taxonomy_term', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.taxonomy_forums');
    $this->assertIdentical('taxonomy_term', $field->getSetting('target_type'));

    // Assert that the entityreference fields are referencing the correct
    // entity type.
    $field = FieldStorageConfig::load('node.field_node_entityreference');
    $this->assertIdentical('node', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_user_entityreference');
    $this->assertIdentical('user', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_term_entityreference');
    $this->assertIdentical('taxonomy_term', $field->getSetting('target_type'));

    // Validate that the source count and processed count match up.
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d7_field');
    $this->assertSame($migration->getSourcePlugin()
      ->count(), $migration->getIdMap()->processedCount());
  }

}
