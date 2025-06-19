<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\FileTransfer;

use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests recursive file copy operations with the file transfer jail.
 *
 * @group FileTransfer
 * @group legacy
 */
class FileTransferTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\Tests\system\Functional\FileTransfer\TestFileTransfer
   */
  protected TestFileTransfer $testConnection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->testConnection = TestFileTransfer::factory($this->root, []);
  }

  /**
   * Returns a predefined list of fake module files for testing.
   */
  public function _getFakeModuleFiles() {
    $files = [
      'fake.module',
      'fake.info.yml',
      'theme' => [
        'fake.html.twig',
      ],
      'inc' => [
        'fake.inc',
      ],
    ];
    return $files;
  }

  /**
   * Builds a fake module directory for testing.
   */
  public function _buildFakeModule() {
    $location = 'temporary://fake';
    if (is_dir($location)) {
      if (!\Drupal::service('file_system')->deleteRecursive($location)) {
        throw new \Exception('Error removing fake module directory.');
      }
    }

    $files = $this->_getFakeModuleFiles();
    $this->_writeDirectory($location, $files);
    return $location;
  }

  /**
   * Writes a directory structure to the filesystem.
   */
  public function _writeDirectory($base, $files = []): void {
    mkdir($base);
    foreach ($files as $key => $file) {
      if (is_array($file)) {
        $this->_writeDirectory($base . DIRECTORY_SEPARATOR . $key, $file);
      }
      else {
        // Just write the filename into the file
        file_put_contents($base . DIRECTORY_SEPARATOR . $file, $file);
      }
    }
  }

  /**
   * Tests the file transfer jail.
   */
  public function testJail(): void {
    $source = $this->_buildFakeModule();

    // This convoluted piece of code is here because our testing framework does
    // not support expecting exceptions.
    $got_it = FALSE;
    try {
      $this->testConnection->copyDirectory($source, sys_get_temp_dir());
    }
    catch (FileTransferException) {
      $got_it = TRUE;
    }
    $this->assertTrue($got_it, 'Was not able to copy a directory outside of the jailed area.');

    $got_it = TRUE;
    try {
      $this->testConnection->copyDirectory($source, $this->root . '/' . PublicStream::basePath());
    }
    catch (FileTransferException) {
      $got_it = FALSE;
    }
    $this->assertTrue($got_it, 'Was able to copy a directory inside of the jailed area');
  }

}
