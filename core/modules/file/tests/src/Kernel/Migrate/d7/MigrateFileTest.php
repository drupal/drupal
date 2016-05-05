<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates all files in the file_managed table.
 *
 * @group file
 */
class MigrateFileTest extends MigrateDrupal7TestBase {

  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')->registerWrapper('public', 'Drupal\Core\StreamWrapper\PublicStream', StreamWrapperInterface::NORMAL);

    $fs = \Drupal::service('file_system');
    // The public file directory active during the test will serve as the
    // root of the fictional Drupal 7 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);
    file_put_contents('public://sites/default/files/cube.jpeg', str_repeat('*', 3620));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file');
    // Set the destination plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $migration->set('destination', [
      'plugin' => 'entity:file',
      // Note that source_base_path must include a trailing slash because it's
      // prepended directly to the value of the source path property.
      'source_base_path' => $fs->realpath('public://') . '/',
      // This is set in the migration's YAML file, but we need to repeat it
      // here because all the destination configuration must be set at once.
      'source_path_property' => 'filepath',
    ]);
    $this->executeMigration($migration);
  }

  /**
   * Tests a single file entity.
   *
   * @param int $id
   *   The file ID.
   * @param string $name
   *   The expected file name.
   * @param string $uri
   *   The expected URI.
   * @param string $mime
   *   The expected MIME type.
   * @param int $size
   *   The expected file size.
   * @param int $created
   *   The expected creation time.
   * @param int $changed
   *   The expected modification time.
   * @param int $uid
   *   The expected owner ID.
   */
  protected function assertEntity($id, $name, $uri, $mime, $size, $created, $changed, $uid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($id);
    $this->assertTrue($file instanceof FileInterface);
    $this->assertIdentical($name, $file->getFilename());
    $this->assertIdentical($uri, $file->getFileUri());
    $this->assertTrue(file_exists($uri));
    $this->assertIdentical($mime, $file->getMimeType());
    $this->assertIdentical($size, $file->getSize());
    // isPermanent(), isTemporary(), etc. are determined by the status column.
    $this->assertTrue($file->isPermanent());
    $this->assertIdentical($created, $file->getCreatedTime());
    $this->assertIdentical($changed, $file->getChangedTime());
    $this->assertIdentical($uid, $file->getOwnerId());
  }

  /**
   * Tests that all expected files are migrated.
   */
  public function testFileMigration() {
    $this->assertEntity(1, 'cube.jpeg', 'public://cube.jpeg', 'image/jpeg', '3620', '1421727515', '1421727515', '1');
  }

}
