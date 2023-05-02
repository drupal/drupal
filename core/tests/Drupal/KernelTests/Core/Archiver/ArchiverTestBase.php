<?php

namespace Drupal\KernelTests\Core\Archiver;

use Drupal\KernelTests\Core\File\FileTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Provides archive specific assertions and helper properties for archive tests.
 */
abstract class ArchiverTestBase extends FileTestBase {
  use TestFileCreationTrait;

  /**
   * The archiver plugin identifier.
   *
   * @var string
   */
  protected $archiverPluginId;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Asserts an archive contains a given file.
   *
   * @param string $path
   *   Absolute file path to an archived file.
   * @param string $file
   *   File to assert does exist within the archived file.
   * @param array $configuration
   *   Optional configuration to pass to the archiver plugin.
   */
  protected function assertArchiveContainsFile($path, $file, array $configuration = []) {
    $configuration['filepath'] = $path;
    /** @var \Drupal\Core\Archiver\ArchiverManager $manager */
    $manager = $this->container->get('plugin.manager.archiver');
    $archive = $manager->createInstance($this->archiverPluginId, $configuration);
    $this->assertContains($file, $archive->listContents(), sprintf('The "%s" archive contains the "%s" file.', $path, $file));
  }

  /**
   * Asserts an archive does not contain a given file.
   *
   * @param string $path
   *   Absolute file path to an archived file.
   * @param string $file
   *   File to assert does not exist within the archived file.
   * @param array $configuration
   *   Optional configuration to pass to the archiver plugin.
   */
  protected function assertArchiveNotContainsFile($path, $file, array $configuration = []) {
    $configuration['filepath'] = $path;
    /** @var \Drupal\Core\Archiver\ArchiverManager $manager */
    $manager = $this->container->get('plugin.manager.archiver');
    $archive = $manager->createInstance($this->archiverPluginId, $configuration);
    $this->assertNotContains($file, $archive->listContents(), sprintf('The "%s" archive does not contain the "%s" file.', $path, $file));
  }

}
