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
  protected static $modules = [
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

  /**
   * Tests migrating D7 fields to field_storage_config entities.
   */
  public function testFields() {
    \Drupal::service('module_installer')->install(['datetime_range']);
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_field');

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
    $this->assertEntity('node.field_text', 'string', TRUE, 1);
    $this->assertEntity('node.field_text_list', 'list_string', TRUE, 3);
    $this->assertEntity('node.field_float_list', 'list_float', TRUE, 1);
    $this->assertEntity('node.field_boolean', 'boolean', TRUE, 1);
    $this->assertEntity('node.field_email', 'email', TRUE, -1);
    $this->assertEntity('node.field_phone', 'telephone', TRUE, 1);
    $this->assertEntity('node.field_date', 'datetime', TRUE, 1);
    $this->assertEntity('node.field_date_with_end_time', 'timestamp', TRUE, 1);
    $this->assertEntity('node.field_node_entityreference', 'entity_reference', TRUE, -1);
    $this->assertEntity('node.field_user_entityreference', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.field_term_entityreference', 'entity_reference', TRUE, -1);
    $this->assertEntity('node.field_date_without_time', 'datetime', TRUE, 1);
    $this->assertEntity('node.field_datetime_without_time', 'datetime', TRUE, 1);
    $this->assertEntity('node.field_file_mfw', 'file', TRUE, 1);
    $this->assertEntity('node.field_image_miw', 'image', TRUE, 1);

    // Tests that fields created by the Title module are not migrated.
    $title_field = FieldStorageConfig::load('node.title_field');
    $this->assertNull($title_field);
    $subject_field = FieldStorageConfig::load('comment.subject_field');
    $this->assertNull($subject_field);
    $name_field = FieldStorageConfig::load('taxonomy_term.name_field');
    $this->assertNull($name_field);
    $description_field = FieldStorageConfig::load('taxonomy_term.description_field');
    $this->assertNull($description_field);

    // Assert that the taxonomy term reference fields are referencing the
    // correct entity type.
    $field = FieldStorageConfig::load('node.field_term_reference');
    $this->assertEquals('taxonomy_term', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.taxonomy_forums');
    $this->assertEquals('taxonomy_term', $field->getSetting('target_type'));

    // Assert that the entityreference fields are referencing the correct
    // entity type.
    $field = FieldStorageConfig::load('node.field_node_entityreference');
    $this->assertEquals('node', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_user_entityreference');
    $this->assertEquals('user', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_term_entityreference');
    $this->assertEquals('taxonomy_term', $field->getSetting('target_type'));

    // Make sure that datetime fields get the right datetime_type setting
    $field = FieldStorageConfig::load('node.field_date');
    $this->assertEquals('datetime', $field->getSetting('datetime_type'));
    $field = FieldStorageConfig::load('node.field_date_without_time');
    $this->assertEquals('date', $field->getSetting('datetime_type'));
    $field = FieldStorageConfig::load('node.field_datetime_without_time');
    $this->assertEquals('date', $field->getSetting('datetime_type'));
    // Except for field_date_with_end_time which is a timestamp and so does not
    // have a datetime_type setting.
    $field = FieldStorageConfig::load('node.field_date_with_end_time');
    $this->assertNull($field->getSetting('datetime_type'));

    // Assert node and user reference fields.
    $field = FieldStorageConfig::load('node.field_node_reference');
    $this->assertEquals('node', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_user_reference');
    $this->assertEquals('user', $field->getSetting('target_type'));

    // Make sure a datetime field with a todate is now a daterange type.
    $field = FieldStorageConfig::load('node.field_event');
    $this->assertSame('daterange', $field->getType());
    $this->assertSame('datetime_range', $field->getTypeProvider());
    $this->assertEquals('datetime', $field->getSetting('datetime_type'));

    // Test the migration of text fields with different text processing.
    // All text and text_long field bases that have only plain text instances
    // should be migrated to string and string_long fields.
    // All text_with_summary field bases that have only plain text instances
    // should not have been migrated since there's no such thing as a
    // string_with_summary field.
    $this->assertEntity('node.field_text_plain', 'string', TRUE, 1);
    $this->assertEntity('node.field_text_long_plain', 'string_long', TRUE, 1);
    $this->assertNull(FieldStorageConfig::load('node.field_text_sum_plain'));

    // All text, text_long and text_with_summary field bases that have only
    // filtered text instances should be migrated to text, text_long and
    // text_with_summary fields.
    $this->assertEntity('node.field_text_filtered', 'text', TRUE, 1);
    $this->assertEntity('node.field_text_long_filtered', 'text_long', TRUE, 1);
    $this->assertEntity('node.field_text_sum_filtered', 'text_with_summary', TRUE, 1);

    // All text, text_long and text_with_summary field bases that have both
    // plain text and filtered text instances should not have been migrated.
    $this->assertNull(FieldStorageConfig::load('node.field_text_plain_filtered'));
    $this->assertNull(FieldStorageConfig::load('node.field_text_long_plain_filtered'));
    $this->assertNull(FieldStorageConfig::load('node.field_text_sum_plain_filtered'));

    // For each text field bases that were skipped, there should be a log
    // message with the required steps to fix this.
    $migration = $this->getMigration('d7_field');
    $errors = array_map(function ($message) {
      return $message->message;
    }, iterator_to_array($migration->getIdMap()->getMessages()));
    sort($errors);
    $this->assertCount(4, $errors);
    $this->assertEquals('d7_field:type: Can\'t migrate source field field_text_long_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text', $errors[0]);
    $this->assertEquals('d7_field:type: Can\'t migrate source field field_text_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text', $errors[1]);
    $this->assertEquals('d7_field:type: Can\'t migrate source field field_text_sum_plain of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text', $errors[2]);
    $this->assertEquals('d7_field:type: Can\'t migrate source field field_text_sum_plain_filtered of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text', $errors[3]);
  }

  /**
   * Tests migrating D7 datetime fields.
   */
  public function testDatetimeFields() {
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_field');

    // Datetime field with 'todate' settings is not migrated.
    $this->assertNull(FieldStorageConfig::load('node.field_event'));

    // Check that we've reported on a conflict in widget_types.
    // Validate that the source count and processed count match up.
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d7_field');
    $messages = iterator_to_array($migration->getIdMap()->getMessages());
    $this->assertCount(5, $messages);
    $msg = "d7_field:type:process_field: Can't migrate field 'field_event' with 'todate' settings. Enable the datetime_range module. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#datetime";
    $this->assertSame($messages[4]->message, $msg);
  }

}
