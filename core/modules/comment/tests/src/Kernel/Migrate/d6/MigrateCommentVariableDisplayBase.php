<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Base class for Drupal 6 comment variables to Drupal 8 entity display tests.
 */
abstract class MigrateCommentVariableDisplayBase extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
    ]);
  }

}
