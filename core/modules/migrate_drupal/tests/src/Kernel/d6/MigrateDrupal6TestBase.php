<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for Drupal 6 migration tests.
 */
abstract class MigrateDrupal6TestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
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
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../fixtures/drupal6.php';
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
   * @param array $include
   *   Extra things to include as part of the migrations. Values may be
   *   'revisions' or 'translations'.
   */
  protected function migrateContent($include = []) {
    if (in_array('translations', $include)) {
      $this->executeMigrations(['language']);
    }
    $this->migrateUsers(FALSE);
    $this->migrateFields();

    $this->installEntitySchema('node');
    $this->executeMigrations(['d6_node_settings', 'd6_node']);

    if (in_array('translations', $include)) {
      $this->executeMigrations(['translations']);
    }
    if (in_array('revisions', $include)) {
      $this->executeMigrations(['d6_node_revision']);
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
