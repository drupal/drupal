<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Base class for Drupal 6 migration tests.
 */
abstract class MigrateDrupal6TestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'entity_reference',
    'filter',
    'image',
    'link',
    'node',
    'options',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installMigrations('Drupal 6');
  }

  /**
     * {@inheritdoc}
     */
  protected function getDumpDirectory() {
    return parent::getDumpDirectory() . '/d6';
  }

  /**
   * Executes all user migrations.
   *
   * @param bool $include_pictures
   *   If TRUE, migrates user pictures.
   */
  protected function migrateUsers($include_pictures = TRUE) {
    $this->executeMigrations(['d6_filter_format', 'd6_user_role']);

    if ($include_pictures) {
      $this->installEntitySchema('file');
      $this->executeMigrations([
        'd6_file',
        'd6_user_picture_file',
        'user_picture_field',
        'user_picture_field_instance',
        'user_picture_entity_display',
        'user_picture_entity_form_display',
      ]);
    }
    else {
      // These are optional dependencies of d6_user, but we don't need them if
      // we're not migrating user pictures.
      Migration::load('d6_user_picture_file')->delete();
      Migration::load('user_picture_entity_display')->delete();
      Migration::load('user_picture_entity_form_display')->delete();
    }

    $this->executeMigration('d6_user');
  }

  /**
   * Migrates node types.
   */
  protected function migrateContentTypes() {
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
  }

  /**
   * Executes all field migrations.
   */
  protected function migrateFields() {
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_field',
      'd6_field_instance',
      'd6_field_instance_widget_settings',
      'd6_view_modes',
      'd6_field_formatter_settings',
      'd6_upload_field',
      'd6_upload_field_instance',
    ]);
  }

  /**
   * Executes all content migrations.
   *
   * @param bool $include_revisions
   *   If TRUE, migrates node revisions.
   */
  protected function migrateContent($include_revisions = FALSE) {
    $this->migrateUsers(FALSE);
    $this->migrateFields();

    $this->installEntitySchema('node');
    $this->executeMigrations(['d6_node_settings', 'd6_node:*']);

    if ($include_revisions) {
      $this->executeMigrations(['d6_node_revision:*']);
    }
  }

  /**
   * Executes all taxonomy migrations.
   */
  protected function migrateTaxonomy() {
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
      'd6_vocabulary_field_instance',
      'd6_vocabulary_entity_display',
      'd6_vocabulary_entity_form_display',
      'd6_taxonomy_term',
    ]);
  }

}
