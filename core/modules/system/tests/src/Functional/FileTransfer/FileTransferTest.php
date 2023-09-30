<?php

namespace Drupal\Tests\system\Functional\FileTransfer;

use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests recursive file copy operations with the file transfer jail.
 *
 * @group FileTransfer
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

  public function _buildFakeModule() {
    $location = 'temporary://fake';
    if (is_dir($location)) {
      $ret = 0;
      $output = [];
      exec('rm -Rf ' . escapeshellarg($location), $output, $ret);
      if ($ret != 0) {
        throw new \Exception('Error removing fake module directory.');
      }
    }

    $files = $this->_getFakeModuleFiles();
    $this->_writeDirectory($location, $files);
    return $location;
  }

  public function _writeDirectory($base, $files = []) {
    mkdir($base);
    foreach ($files as $key => $file) {
      if (is_array($file)) {
        $this->_writeDirectory($base . DIRECTORY_SEPARATOR . $key, $file);
      }
      else {
        // just write the filename into the file
        file_put_contents($base . DIRECTORY_SEPARATOR . $file, $file);
      }
    }
  }

  public function testJail() {
    $source = $this->_buildFakeModule();

    // This convoluted piece of code is here because our testing framework does
    // not support expecting exceptions.
    $got_it = FALSE;
    try {
      $this->testConnection->copyDirectory($source, sys_get_temp_dir());
    }
    catch (FileTransferException $e) {
      $got_it = TRUE;
    }
    $this->assertTrue($got_it, 'Was not able to copy a directory outside of the jailed area.');

    $got_it = TRUE;
    try {
      $this->testConnection->copyDirectory($source, $this->root . '/' . PublicStream::basePath());
    }
    catch (FileTransferException $e) {
      $got_it = FALSE;
    }
    $this->assertTrue($got_it, 'Was able to copy a directory inside of the jailed area');
  }

}
