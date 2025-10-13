<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Database\Connection;
use Drupal\Core\Test\EnvironmentCleaner;
use Drupal\Core\Test\TestRunResultsStorageInterface;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Tests Drupal\Core\Test\EnvironmentCleaner.
 */
#[CoversClass(EnvironmentCleaner::class)]
#[Group('Test')]
#[RunTestsInSeparateProcesses]
class EnvironmentCleanerTest extends KernelTestBase {

  /**
   * Tests do clean temporary directories.
   *
   * @legacy-covers ::doCleanTemporaryDirectories
   */
  public function testDoCleanTemporaryDirectories(): void {
    vfsStream::setup('cleanup_test', NULL, [
      'sites' => [
        'simpletest' => [
          'delete_dir' => [
            'delete.me' => 'I am gone.',
          ],
          'delete_me.too' => 'delete this file.',
        ],
      ],
    ]);

    $connection = $this->prophesize(Connection::class);
    $test_run_results_storage = $this->prophesize(TestRunResultsStorageInterface::class);

    $cleaner = new EnvironmentCleaner(
      vfsStream::url('cleanup_test'),
      $connection->reveal(),
      $test_run_results_storage->reveal(),
      new NullOutput(),
      \Drupal::service('file_system')
    );

    $do_cleanup_ref = new \ReflectionMethod($cleaner, 'doCleanTemporaryDirectories');

    $this->assertFileExists(vfsStream::url('cleanup_test/sites/simpletest/delete_dir/delete.me'));
    $this->assertFileExists(vfsStream::url('cleanup_test/sites/simpletest/delete_me.too'));

    $this->assertEquals(2, $do_cleanup_ref->invoke($cleaner));

    $this->assertDirectoryDoesNotExist(vfsStream::url('cleanup_test/sites/simpletest/delete_dir'));
    $this->assertFileDoesNotExist(vfsStream::url('cleanup_test/sites/simpletest/delete_dir/delete.me'));
    $this->assertFileDoesNotExist(vfsStream::url('cleanup_test/sites/simpletest/delete_me.too'));
  }

}
