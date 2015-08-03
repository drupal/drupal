<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d6\MigrateUserPictureFileTest.
 */

namespace Drupal\user\Tests\Migrate\d6;

use Drupal\file\Entity\File;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * User pictures migration.
 *
 * @group user
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
    $this->loadDumps([
      'Users.php',
      'ProfileValues.php',
      'UsersRoles.php',
      'EventTimezones.php',
    ]);

    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $migration = entity_load('migration', 'd6_user_picture_file');
    $source = $migration->get('source');
    $source['site_path'] = 'core/modules/simpletest';
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

  /**
   * Tests the Drupal 6 user pictures to Drupal 8 migration.
   */
  public function testUserPictures() {
    $file_ids = array();
    foreach (entity_load('migration', 'd6_user_picture_file')->getIdMap() as $destination_ids) {
      $file_ids[] = reset($destination_ids);
    }
    $files = File::loadMultiple($file_ids);
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
