<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests \Drupal\Core\File\FileSystemInterface::scanDirectory().
 */
#[CoversClass(FileSystem::class)]
#[Group('File')]
#[RunTestsInSeparateProcesses]
class RemoteFileScanDirectoryTest extends ScanDirectoryTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy-remote';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\StreamWrapper\DummyRemoteStreamWrapper';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }

}
