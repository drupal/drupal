<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests private files migration.
 *
 * @group file
 */
class MigratePrivateFileTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setSetting('file_private_path', $this->container->get('site.path') . '/private');
    $this->installEntitySchema('file');
    $fs = $this->container->get('file_system');

    // Ensure that the private files directory exists.
    $fs->mkdir('private://sites/default/private/', NULL, TRUE);
    // Put test file in the source directory.
    file_put_contents('private://sites/default/private/Babylon5.txt', str_repeat('*', 3));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file_private');
    // Set the source plugin's source_file_private_path configuration value,
    // which would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath('private://');
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);
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
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertFileExists($uri);
    $this->assertSame($mime, $file->getMimeType());
    $this->assertSame($size, $file->getSize());
    // isPermanent(), isTemporary(), etc. are determined by the status column.
    $this->assertTrue($file->isPermanent());
    $this->assertSame($created, $file->getCreatedTime());
    $this->assertSame($changed, $file->getChangedTime());
    $this->assertSame($uid, $file->getOwnerId());
  }

  /**
   * Tests that all expected files are migrated.
   */
  public function testFileMigration() {
    $this->assertEntity(3, 'Babylon5.txt', 'private://Babylon5.txt', 'text/plain', '3', '1486104045', '1486104045', '1');
  }

}
