<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;

/**
 * Migrates and rolls back Drupal 7 fields.
 *
 * @group field
 */
class RollbackFieldTest extends MigrateFieldTest {

  /**
   * Tests migrating D7 fields to field_storage_config entities, then rolling back.
   */
  public function testFields() {
    // Test that the fields have migrated (prior to rollback).
    parent::testFields();

    $this->executeRollback('d7_field');

    // Check that fields have been rolled back.
    $rolled_back_field_ids = [
      'comment.field_integer',
      'node.taxonomy_forums',
      'node.field_integer',
      'node.field_tags',
      'node.field_term_reference',
      'node.field_text_list',
      'node.field_text',
      'node.field_phone',
      'node.field_file',
      'node.field_images',
      'node.field_image',
      'node.field_long_text',
      'node.field_date_with_end_time',
      'node.field_integer_list',
      'node.field_date',
      'node.field_link',
      'node.field_float',
      'node.field_boolean',
      'node.field_email',
      'user.field_file',
    ];
    foreach ($rolled_back_field_ids as $field_id) {
      $this->assertNull(FieldStorageConfig::load($field_id));
    }

    // Check that fields that should persist have not been rolled back.
    $non_rolled_back_field_ids = [
      'node.body',
      'comment.comment_body',
    ];
    foreach ($non_rolled_back_field_ids as $field_id) {
      $this->assertNotNull(FieldStorageConfig::load($field_id));
    }
  }

  /**
   * Executes a single rollback.
   *
   * @param string|\Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to rollback, or its ID.
   */
  protected function executeRollback($migration) {
    if (is_string($migration)) {
      $this->migration = $this->getMigration($migration);
    }
    else {
      $this->migration = $migration;
    }
    (new MigrateExecutable($this->migration, $this))->rollback();
  }

}
