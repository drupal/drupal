<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * User pictures migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserPictureD6FileTest extends MigrateDrupal6TestBase {

  use FileMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->executeMigration('d6_user_picture_file');
    $this->setUpMigratedFiles();
  }

  /**
   * Asserts a file entity.
   *
   * @param int $fid
   *   The file ID.
   * @param string $name
   *   The expected file name.
   * @param int $size
   *   The expected file size.
   * @param string $uri
   *   The expected file URI.
   * @param string $type
   *   The expected MIME type.
   * @param int $uid
   *   The expected file owner ID.
   */
  protected function assertEntity($fid, $name, $size, $uri, $type, $uid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($size, $file->getSize());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertSame($type, $file->getMimeType());
    $this->assertSame($uid, $file->getOwnerId());
  }

  /**
   * Tests the D6 user pictures migration in combination with D6 file.
   */
  public function testUserPicturesWithD6File() {
    $this->assertEntity(1, 'image-test.jpg', '1901', 'public://image-test.jpg', 'image/jpeg', '2');
    $this->assertEntity(2, 'image-test.png', '125', 'public://image-test.png', 'image/png', '8');
    $this->assertEntity(3, 'Image1.png', '39325', 'public://image-1.png', 'image/png', '1');
    $this->assertEntity(4, 'Image2.jpg', '1831', 'public://image-2.jpg', 'image/jpeg', '1');
    $this->assertEntity(5, 'Image-test.gif', '183', 'public://image-test.gif', 'image/jpeg', '1');
    $this->assertEntity(6, 'html-1.txt', '24', 'public://html-1.txt', 'text/plain', '1');
  }

}
