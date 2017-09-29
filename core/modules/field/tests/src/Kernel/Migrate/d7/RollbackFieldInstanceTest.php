<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\MigrateExecutable;

/**
 * Migrates and rolls back Drupal 7 fields.
 *
 * @group field
 */
class RollbackFieldInstanceTest extends MigrateFieldInstanceTest {

  /**
   * Tests migrating D7 fields to field_storage_config entities, then rolling back.
   */
  public function testFieldInstances() {
    // Test that the field instances have migrated (prior to rollback).
    parent::testFieldInstances();

    $this->executeRollback('d7_field_instance');
    $this->executeRollback('d7_field');

    // Check that field instances have been rolled back.
    $field_instance_ids = [
      'comment.comment_node_page.comment_body',
      'node.page.body',
      'comment.comment_node_article.comment_body',
      'node.article.body',
      'node.article.field_tags',
      'node.article.field_image',
      'comment.comment_node_blog.comment_body',
      'node.blog.body',
      'comment.comment_node_book.comment_body',
      'node.book.body',
      'node.forum.taxonomy_forums',
      'comment.comment_forum.comment_body',
      'node.forum.body',
      'comment.comment_node_test_content_type.comment_body',
      'node.test_content_type.field_boolean',
      'node.test_content_type.field_email',
      'node.test_content_type.field_phone',
      'node.test_content_type.field_date',
      'node.test_content_type.field_date_with_end_time',
      'node.test_content_type.field_file',
      'node.test_content_type.field_float',
      'node.test_content_type.field_images',
      'node.test_content_type.field_integer',
      'node.test_content_type.field_link',
      'node.test_content_type.field_text_list',
      'node.test_content_type.field_integer_list',
      'node.test_content_type.field_long_text',
      'node.test_content_type.field_term_reference',
      'node.test_content_type.field_text',
      'comment.comment_node_test_content_type.field_integer',
      'user.user.field_file',
    ];
    foreach ($field_instance_ids as $field_instance_id) {
      $this->assertNull(FieldConfig::load($field_instance_id));
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
