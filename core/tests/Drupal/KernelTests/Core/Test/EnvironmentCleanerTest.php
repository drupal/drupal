<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Database\Connection;
use Drupal\Core\Test\EnvironmentCleaner;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @coversDefaultClass \Drupal\Core\Test\EnvironmentCleaner
 * @group Test
 */
class EnvironmentCleanerTest extends KernelTestBase {

  /**
   * @covers ::doCleanTemporaryDirectories
   */
  public function testDoCleanTemporaryDirectories() {
    vfsStream::setup('cleanup_test', NULL, [
      'sites' => [
        'simpletest' => [
          'delete_dir' => [
            'delete.me' => 'I am a gonner.',
          ],
          'delete_me.too' => 'delete this file.',
        ],
      ],
    ]);

    $connection = $this->prophesize(Connection::class);

    $cleaner = new EnvironmentCleaner(
      vfsStream::url('cleanup_test'),
      $connection->reveal(),
      $connection->reveal(),
      new NullOutput(),
      \Drupal::service('file_system')
    );

    $do_cleanup_ref = new \ReflectionMethod($cleaner, 'doCleanTemporaryDirectories');
    $do_cleanup_ref->setAccessible(TRUE);

    $this->assertFileExists(vfsStream::url('cleanup_test/sites/simpletest/delete_dir/delete.me'));
    $this->assertFileExists(vfsStream::url('cleanup_test/sites/simpletest/delete_me.too'));

    $this->assertEquals(2, $do_cleanup_ref->invoke($cleaner));

    $this->assertDirectoryNotExists(vfsStream::url('cleanup_test/sites/simpletest/delete_dir'));
    $this->assertFileNotExists(vfsStream::url('cleanup_test/sites/simpletest/delete_dir/delete.me'));
    $this->assertFileNotExists(vfsStream::url('cleanup_test/sites/simpletest/delete_me.too'));
  }

}
