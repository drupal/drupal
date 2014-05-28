<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemImageTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of image toolkit variables to configuration.
 */
class MigrateSystemImageTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate image variables to system.*.yml',
      'description'  => 'Upgrade image variables to system.*.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_image');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemImage.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (image) variables to system.image.yml.
   */
  public function testSystemImage() {
    $config = \Drupal::config('system.image');
    $this->assertIdentical($config->get('toolkit'), 'gd');
  }

}
