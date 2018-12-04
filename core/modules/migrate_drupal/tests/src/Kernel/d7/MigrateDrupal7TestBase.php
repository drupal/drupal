<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for Drupal 7 migration tests.
 */
abstract class MigrateDrupal7TestBase extends MigrateDrupalTestBase {

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
    return __DIR__ . '/../../../fixtures/drupal7.php';
  }

  /**
   * Executes all field migrations.
   */
  protected function migrateFields() {
    $this->executeMigration('d7_field');
    $this->migrateContentTypes();
    $this->migrateCommentTypes();
    $this->executeMigrations(['d7_taxonomy_vocabulary', 'd7_field_instance']);
  }

  /**
   * Executes all user migrations.
   *
   * @param bool $include_pictures
   *   (optional) If TRUE, migrates user pictures. Defaults to TRUE.
   */
  protected function migrateUsers($include_pictures = TRUE) {
    $migrations = ['d7_user_role', 'd7_user'];

    if ($include_pictures) {
      // Prepare to migrate user pictures as well.
      $this->installEntitySchema('file');
      $migrations = array_merge([
        'user_picture_field',
        'user_picture_field_instance',
      ], $migrations);
    }

    $this->executeMigrations($migrations);
  }

  /**
   * Migrates node types.
   */
  protected function migrateContentTypes() {
    $this->installConfig(['node']);
    $this->installEntitySchema('node');
    $this->executeMigration('d7_node_type');
  }

  /**
   * Migrates comment types.
   */
  protected function migrateCommentTypes() {
    $this->installConfig(['comment']);
    $this->executeMigration('d7_comment_type');
  }

  /**
   * Executes all content migrations.
   *
   * @param bool $include_revisions
   *   (optional) If TRUE, migrates node revisions. Defaults to FALSE.
   */
  protected function migrateContent($include_revisions = FALSE) {
    $this->migrateContentTypes();
    $this->migrateCommentTypes();

    $this->migrateUsers(FALSE);
    // Uses executeMigrations() rather than executeMigration() because the
    // former includes all of the migration derivatives, e.g.
    // d7_node:article.
    $this->executeMigrations(['d7_node']);

    if ($include_revisions) {
      $this->executeMigrations(['d7_node_revision']);
    }
  }

  /**
   * Executes all taxonomy term migrations.
   */
  protected function migrateTaxonomyTerms() {
    $this->installEntitySchema('taxonomy_term');
    $this->migrateFields();
    // Uses executeMigrations() rather than executeMigration() because the
    // former includes all of the migration derivatives, e.g.
    // d7_taxonomy_term:tags.
    $this->executeMigrations(['d7_taxonomy_term']);
  }

}
