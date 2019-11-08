<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\file\Entity\File;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * User pictures migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserPictureFileTest extends MigrateDrupal6TestBase {

  use FileMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->executeMigration('d6_user_picture_file');
  }

  /**
   * Tests the Drupal 6 user pictures to Drupal 8 migration.
   */
  public function testUserPictures() {
    $file_ids = [];
    foreach ($this->migration->getIdMap() as $destination_ids) {
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
    $this->assertEmpty($files);
  }

}
