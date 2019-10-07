<?php

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\Framework\BuildTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Finder\Finder;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\BuildTestBase
 * @group Build
 */
class BuildTestTest extends BuildTestBase {

  /**
   * Ensure that workspaces work.
   */
  public function testWorkspace() {
    $test_directory = 'test_directory';

    // Execute an empty command through the shell to build out a working
    // directory.
    $process = $this->executeCommand('', $test_directory);
    $this->assertCommandSuccessful();

    // Assert that our working directory exists and is in use by the process.
    $workspace = $this->getWorkspaceDirectory();
    $working_path = $workspace . '/' . $test_directory;
    $this->assertDirectoryExists($working_path);
    $this->assertEquals($working_path, $process->getWorkingDirectory());
  }

  /**
   * @covers ::copyCodebase
   */
  public function testCopyCodebase() {
    $test_directory = 'copied_codebase';
    $this->copyCodebase(NULL, $test_directory);
    $full_path = $this->getWorkspaceDirectory() . '/' . $test_directory;
    $files = [
      'autoload.php',
      'composer.json',
      'index.php',
      'README.txt',
      '.git',
      '.ht.router.php',
    ];
    foreach ($files as $file) {
      $this->assertFileExists($full_path . '/' . $file);
    }
  }

  /**
   * Ensure we're not copying directories we wish to exclude.
   *
   * @covers ::copyCodebase
   */
  public function testCopyCodebaseExclude() {
    // Create a virtual file system containing only items that should be
    // excluded.
    vfsStream::setup('drupal', NULL, [
      'sites' => [
        'default' => [
          'files' => [
            'a_file.txt' => 'some file.',
          ],
          'settings.php' => '<?php $settings = stuff;',
        ],
        'simpletest' => [
          'simpletest_hash' => [
            'some_results.xml' => '<xml/>',
          ],
        ],
      ],
      'vendor' => [
        'composer' => [
          'composer' => [
            'installed.json' => '"items": {"things"}',
          ],
        ],
      ],
    ]);

    // Mock BuildTestBase so that it thinks our VFS is the Drupal root.
    /** @var \PHPUnit\Framework\MockObject\MockBuilder|\Drupal\BuildTests\Framework\BuildTestBase $base */
    $base = $this->getMockBuilder(BuildTestBase::class)
      ->setMethods(['getDrupalRoot'])
      ->getMockForAbstractClass();
    $base->expects($this->exactly(2))
      ->method('getDrupalRoot')
      ->willReturn(vfsStream::url('drupal'));

    $base->setUp();

    // Perform the copy.
    $test_directory = 'copied_codebase';
    $base->copyCodebase(NULL, $test_directory);
    $full_path = $base->getWorkspaceDirectory() . '/' . $test_directory;

    $this->assertDirectoryExists($full_path);
    // Use scandir() to determine if our target directory is empty. It should
    // only contain the system dot directories.
    $this->assertTrue(
      ($files = @scandir($full_path)) && count($files) <= 2,
      'Directory is not empty: ' . implode(', ', $files)
    );

    $base->tearDown();
  }

  /**
   * @covers ::findAvailablePort
   */
  public function testPortMany() {
    $iterator = (new Finder())->in($this->getDrupalRoot())
      ->ignoreDotFiles(FALSE)
      ->exclude(['sites/simpletest'])
      ->path('/^.ht.router.php$/')
      ->getIterator();
    $this->copyCodebase($iterator);
    /** @var \Symfony\Component\Process\Process[] $processes */
    $processes = [];
    $count = 15;
    for ($i = 0; $i <= $count; $i++) {
      $port = $this->findAvailablePort();
      $this->assertArrayNotHasKey($port, $processes, 'Port ' . $port . ' was already in use by a process.');
      $processes[$port] = $this->instantiateServer($port);
      $this->assertNotEmpty($processes[$port]);
      $this->assertTrue($processes[$port]->isRunning(), 'Process on port ' . $port . ' is not still running.');
      $this->assertFalse($this->checkPortIsAvailable($port));
    }

    // Clean up after ourselves.
    foreach ($processes as $process) {
      $process->stop();
    }
  }

}
