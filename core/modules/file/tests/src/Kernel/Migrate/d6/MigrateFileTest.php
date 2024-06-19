<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Database\Database;
use Drupal\Tests\migrate\Kernel\MigrateDumpAlterInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Test file migration.
 *
 * @group migrate_drupal_6
 */
class MigrateFileTest extends MigrateDrupal6TestBase implements MigrateDumpAlterInterface {

  use FileMigrationTestTrait;

  /**
   * The filename of a file used to test temporary file migration.
   *
   * @var string
   */
  protected static $tempFilename;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Remove the file_directory_path to test site_path setting.
    // @see \Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait::prepareMigration()
    Database::getConnection('default', 'migrate')
      ->delete('variable')
      ->condition('name', 'file_directory_path')
      ->execute();

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
   *
   * @internal
   */
  protected function assertEntity(int $fid, string $name, int $size, string $uri, string $type, int $uid): void {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($size, (int) $file->getSize());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertSame($type, $file->getMimeType());
    $this->assertSame($uid, (int) $file->getOwnerId());
  }

  /**
   * Tests the Drupal 6 files to Drupal 8 migration.
   */
  public function testFiles(): void {
    $this->assertEntity(1, 'Image1.png', 39325, 'public://image-1.png', 'image/png', 1);
    $this->assertEntity(2, 'Image2.jpg', 1831, 'public://image-2.jpg', 'image/jpeg', 1);
    $this->assertEntity(3, 'image-3.jpg', 1831, 'public://image-3.jpg', 'image/jpeg', 1);
    $this->assertEntity(4, 'html-1.txt', 19, 'public://html-1.txt', 'text/plain', 1);
    // Ensure temporary file was not migrated.
    $this->assertNull(File::load(6));

    $map_table = $this->getMigration('d6_file')->getIdMap()->mapTableName();
    $map = \Drupal::database()
      ->select($map_table, 'm')
      ->fields('m', ['sourceid1', 'destid1'])
      ->execute()
      ->fetchAllKeyed();
    $map_expected = [
      // The 4 files from the fixture.
      1 => '1',
      2 => '2',
      // The file updated in migrateDumpAlter().
      3 => '3',
      5 => '4',
      // The file created in migrateDumpAlter().
      7 => '4',
    ];
    $this->assertEquals($map_expected, $map);

    // Test that we can re-import and also test with file_directory_path set.
    \Drupal::database()
      ->truncate($map_table)
      ->execute();

    // Set the file_directory_path.
    Database::getConnection('default', 'migrate')
      ->insert('variable')
      ->fields(['name', 'value'])
      ->values(['name' => 'file_directory_path', 'value' => serialize('files/test')])
      ->execute();

    $this->executeMigration('d6_file');

    // File 2, when migrated for the second time, is treated as a different file
    // (due to having a different uri this time) and is given fid 6.
    $file = File::load(6);
    $this->assertSame('public://core/tests/fixtures/files/image-2.jpg', $file->getFileUri());

    $map_table = $this->getMigration('d6_file')->getIdMap()->mapTableName();
    $map = \Drupal::database()
      ->select($map_table, 'm')
      ->fields('m', ['sourceid1', 'destid1'])
      ->execute()
      ->fetchAllKeyed();
    $map_expected = [
      // The 4 files from the fixture.
      1 => '5',
      2 => '6',
      // The file updated in migrateDumpAlter().
      3 => '7',
      5 => '8',
      // The files created in migrateDumpAlter().
      7 => '8',
      8 => '8',
    ];
    $this->assertEquals($map_expected, $map);

    // File 6, created in static::migrateDumpAlter(), shares a path with
    // file 4, which means it should be skipped entirely. If it was migrated
    // then it would have an fid of 9.
    $this->assertNull(File::load(9));

    $this->assertCount(8, File::loadMultiple());
  }

  /**
   * {@inheritdoc}
   */
  public static function migrateDumpAlter(KernelTestBase $test) {
    $db = Database::getConnection('default', 'migrate');

    $db->update('files')
      ->condition('fid', 3)
      ->fields([
        'filename' => 'image-3.jpg',
        'filepath' => 'core/tests/fixtures/files/image-3.jpg',
      ])
      ->execute();

    $file = (array) $db->select('files')
      ->fields('files')
      ->condition('fid', 5)
      ->execute()
      ->fetchObject();
    unset($file['fid']);
    $db->insert('files')->fields($file)->execute();

    return static::$tempFilename;
  }

}
