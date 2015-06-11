<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureFileTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * User pictures migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureFileTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');

    $dumps = array(
      $this->getDumpDirectory() . '/Users.php',
      $this->getDumpDirectory() . '/ProfileValues.php',
      $this->getDumpDirectory() . '/UsersRoles.php',
      $this->getDumpDirectory() . '/EventTimezones.php',
    );
    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $migration = entity_load('migration', 'd6_user_picture_file');
    $source = $migration->get('source');
    $source['site_path'] = 'core/modules/simpletest';
    $migration->set('source', $source);
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 user pictures to Drupal 8 migration.
   */
  public function testUserPictures() {
    $file_ids = array();
    foreach (entity_load('migration', 'd6_user_picture_file')->getIdMap() as $destination_ids) {
      $file_ids[] = reset($destination_ids);
    }
    $files = entity_load_multiple('file', $file_ids);
    /** @var \Drupal\file\FileInterface $file */
    $file = array_shift($files);
    $this->assertIdentical('image-test.jpg', $file->getFilename());
    $this->assertIdentical('public://image-test.jpg', $file->getFileUri());
    $this->assertIdentical('2', $file->getOwnerId());
    $this->assertIdentical('1901', $file->getSize());
    $this->assertIdentical('image/jpeg', $file->getMimeType());

    $file = array_shift($files);
    $this->assertIdentical('image-test.png', $file->getFilename());
    $this->assertIdentical('public://image-test.png', $file->getFileUri());
    $this->assertIdentical('8', $file->getOwnerId());
    $this->assertFalse($files);
  }

}
