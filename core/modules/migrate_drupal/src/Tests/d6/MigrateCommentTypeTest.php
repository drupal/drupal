<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentTypeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 to Drupal 8 comment type migration.
 */
class MigrateCommentTypeTest extends MigrateDrupalTestBase {

  static $modules = array('node', 'comment');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate comment type.',
      'description'  => 'Upgrade comment type.',
      'group' => 'Migrate Drupal',
    );
  }


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment_type');

    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemSite.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment type migration.
   */
  public function testCommentType() {
    $comment_type = entity_load('comment_type', 'comment');
    $this->assertEqual('node', $comment_type->getTargetEntityTypeId());
  }
}
