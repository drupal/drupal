<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\Framework\BuildTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\BuildTestBase
 * @group Build
 */
class BuildTestTest extends BuildTestBase {

  /**
   * Ensure that workspaces work.
   */
  public function testWorkspace(): void {
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
  public function testCopyCodebase(): void {
    $test_directory = 'copied_codebase';
    $this->copyCodebase(NULL, $test_directory);
    $full_path = $this->getWorkspaceDirectory() . '/' . $test_directory;
    $files = [
      'autoload.php',
      'composer.json',
      'index.php',
      'README.md',
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
  public function testCopyCodebaseExclude(): void {
    // Create a virtual file system containing items that should be
    // excluded. Exception being modules directory.
    vfsStream::setup('drupal', NULL, [
      'sites' => [
        'default' => [
          'files' => [
            'a_file.txt' => 'some file.',
          ],
          'settings.php' => '<?php $settings = stuff;',
          'settings.local.php' => '<?php $settings = override;',
        ],
        'simpletest' => [
          'simpletest_hash' => [
            'some_results.xml' => '<xml/>',
          ],
        ],
      ],
      'modules' => [
        'my_module' => [
          'vendor' => [
            'my_vendor' => [
              'composer.json' => "{\n}",
            ],
          ],
        ],
      ],
    ]);

    // Mock BuildTestBase so that it thinks our VFS is the Composer and Drupal
    // roots.
    /** @var \PHPUnit\Framework\MockObject\MockBuilder|\Drupal\BuildTests\Framework\BuildTestBase $base */
    $base = $this->getMockBuilder(BuildTestBase::class)
      ->onlyMethods(['getDrupalRoot', 'getComposerRoot'])
      ->setConstructorArgs(['test'])
      ->getMock();
    $base->expects($this->exactly(1))
      ->method('getDrupalRoot')
      ->willReturn(vfsStream::url('drupal'));
    $base->expects($this->exactly(3))
      ->method('getComposerRoot')
      ->willReturn(vfsStream::url('drupal'));

    $base->setUp();

    // Perform the copy.
    $test_directory = 'copied_codebase';
    $base->copyCodebase(NULL, $test_directory);
    $full_path = $base->getWorkspaceDirectory() . '/' . $test_directory;

    $this->assertDirectoryExists($full_path);

    // Verify nested vendor directory was not excluded. Then remove it for next
    // validation.
    $this->assertFileExists($full_path . DIRECTORY_SEPARATOR . 'modules/my_module/vendor/my_vendor/composer.json');
    $file_system = new Filesystem();
    $file_system->remove($full_path . DIRECTORY_SEPARATOR . 'modules');

    // Use scandir() to determine if our target directory is empty. It should
    // only contain the system dot directories.
    $this->assertTrue(
      ($files = @scandir($full_path)) && count($files) <= 2,
      'Directory is not empty: ' . implode(', ', $files)
    );

    $base->tearDown();
  }

  /**
   * Tests copying codebase when Drupal and Composer roots are different.
   *
   * @covers ::copyCodebase
   */
  public function testCopyCodebaseDocRoot(): void {
    // Create a virtual file system containing items that should be
    // excluded. Exception being modules directory.
    vfsStream::setup('drupal', NULL, [
      'docroot' => [
        'sites' => [
          'default' => [
            'files' => [
              'a_file.txt' => 'some file.',
            ],
            'settings.php' => '<?php $settings = "stuff";',
            'settings.local.php' => '<?php $settings = "override";',
            'default.settings.php' => '<?php $settings = "default";',
          ],
          'simpletest' => [
            'simpletest_hash' => [
              'some_results.xml' => '<xml/>',
            ],
          ],
        ],
        'modules' => [
          'my_module' => [
            'vendor' => [
              'my_vendor' => [
                'composer.json' => "{\n}",
              ],
            ],
          ],
        ],
      ],
      'vendor' => [
        'test.txt' => 'File exists',
      ],
    ]);

    // Mock BuildTestBase so that it thinks our VFS is the Composer and Drupal
    // roots.
    /** @var \PHPUnit\Framework\MockObject\MockBuilder|\Drupal\BuildTests\Framework\BuildTestBase $base */
    $base = $this->getMockBuilder(BuildTestBase::class)
      ->onlyMethods(['getDrupalRoot', 'getComposerRoot'])
      ->setConstructorArgs(['test'])
      ->getMock();
    $base->expects($this->exactly(3))
      ->method('getDrupalRoot')
      ->willReturn(vfsStream::url('drupal/docroot'));
    $base->expects($this->exactly(5))
      ->method('getComposerRoot')
      ->willReturn(vfsStream::url('drupal'));

    $base->setUp();

    // Perform the copy.
    $base->copyCodebase();
    $full_path = $base->getWorkspaceDirectory();

    $this->assertDirectoryExists($full_path . '/docroot');

    // Verify expected files exist.
    $this->assertFileExists($full_path . DIRECTORY_SEPARATOR . 'docroot/modules/my_module/vendor/my_vendor/composer.json');
    $this->assertFileExists($full_path . DIRECTORY_SEPARATOR . 'docroot/sites/default/default.settings.php');
    $this->assertFileExists($full_path . DIRECTORY_SEPARATOR . 'vendor');

    // Verify expected files do not exist
    $this->assertFileDoesNotExist($full_path . DIRECTORY_SEPARATOR . 'docroot/sites/default/settings.php');
    $this->assertFileDoesNotExist($full_path . DIRECTORY_SEPARATOR . 'docroot/sites/default/settings.local.php');
    $this->assertFileDoesNotExist($full_path . DIRECTORY_SEPARATOR . 'docroot/sites/default/files');

    // Ensure that the workspace Drupal root is calculated correctly.
    $this->assertSame($full_path . '/docroot/', $base->getWorkspaceDrupalRoot());
    $this->assertSame('docroot/', $base->getWorkingPathDrupalRoot());

    $base->tearDown();
  }

  /**
   * @covers ::findAvailablePort
   */
  public function testPortMany(): void {
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

  /**
   * @covers ::standUpServer
   */
  public function testStandUpServer(): void {
    // Stand up a server with working directory 'first'.
    $this->standUpServer('first');

    // Get the process object for the server.
    $ref_process = new \ReflectionProperty(parent::class, 'serverProcess');
    $first_process = $ref_process->getValue($this);

    // Standing up the server again should not change the server process.
    $this->standUpServer('first');
    $this->assertSame($first_process, $ref_process->getValue($this));

    // Standing up the server with working directory 'second' should give us a
    // new server process.
    $this->standUpServer('second');
    $this->assertNotSame(
      $first_process,
      $second_process = $ref_process->getValue($this)
    );

    // And even with the original working directory name, we should get a new
    // server process.
    $this->standUpServer('first');
    $this->assertNotSame($first_process, $ref_process->getValue($this));
    $this->assertNotSame($second_process, $ref_process->getValue($this));
  }

}
