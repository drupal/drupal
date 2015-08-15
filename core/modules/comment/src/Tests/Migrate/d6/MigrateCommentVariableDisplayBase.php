<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d6\MigrateCommentVariableDisplayBase.
 */

namespace Drupal\comment\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Base class for Drupal 6 comment variables to Drupal 8 entity display tests.
 */
abstract class MigrateCommentVariableDisplayBase extends MigrateDrupal6TestBase {

  /**
   * The ID of migration to run.
   *
   * This constant needs to be set in the concrete class in order for the test
   * to work.
   */
  const MIGRATION = '';

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('comment', 'node');

  /**
   * The node types being tested.
   *
   * @var array
   */
  protected $types = array('page', 'story', 'article');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'comment',
      'type' => 'comment',
      'translatable' => '0',
    ))->save();
    foreach ($this->types as $type) {
      entity_create('node_type', array('type' => $type))->save();
      entity_create('field_config', array(
        'label' => 'Comments',
        'description' => '',
        'field_name' => 'comment',
        'entity_type' => 'node',
        'bundle' => $type,
        'required' => 1,
      ))->save();
    }
    $id_mappings = array(
      'd6_comment_field_instance' => array(
        array(array('page'), array('node', 'comment', 'page')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->executeMigration(static::MIGRATION);

  }

}
