<?php

/**
 * @file
 * Definition of Drupal\system\Tests\FileTransfer\FileTransferTest.
 */

namespace Drupal\system\Tests\FileTransfer;

use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that the jail is respected and that protocols using recursive file move
 * operations work.
 *
 * @group FileTransfer
 */
class FileTransferTest extends WebTestBase {
  protected $hostname = 'localhost';
  protected $username = 'drupal';
  protected $password = 'password';
  protected $port = '42';

  protected function setUp() {
    parent::setUp();
    $this->testConnection = TestFileTransfer::factory(\Drupal::root(), array('hostname' => $this->hostname, 'username' => $this->username, 'password' => $this->password, 'port' => $this->port));
  }

  function _getFakeModuleFiles() {
    $files = array(
      'fake.module',
      'fake.info.yml',
      'theme' => array(
        'fake.html.twig'
      ),
      'inc' => array(
        'fake.inc'
      )
    );
    return $files;
  }

  function _buildFakeModule() {
    $location = 'temporary://fake';
    if (is_dir($location)) {
      $ret = 0;
      $output = array();
      exec('rm -Rf ' . escapeshellarg($location), $output, $ret);
      if ($ret != 0) {
        throw new Exception('Error removing fake module directory.');
      }
    }

    $files = $this->_getFakeModuleFiles();
    $this->_writeDirectory($location, $files);
    return $location;
  }

  function _writeDirectory($base, $files = array()) {
    mkdir($base);
    foreach ($files as $key => $file) {
      if (is_array($file)) {
        $this->_writeDirectory($base . DIRECTORY_SEPARATOR . $key, $file);
      }
      else {
        //just write the filename into the file
        file_put_contents($base . DIRECTORY_SEPARATOR . $file, $file);
      }
    }
  }

  function testJail() {
    $source = $this->_buildFakeModule();

    // This convoluted piece of code is here because our testing framework does
    // not support expecting exceptions.
    $gotit = FALSE;
    try {
      $this->testConnection->copyDirectory($source, sys_get_temp_dir());
    }
    catch (FileTransferException $e) {
      $gotit = TRUE;
    }
    $this->assertTrue($gotit, 'Was not able to copy a directory outside of the jailed area.');

    $gotit = TRUE;
    try {
      $this->testConnection->copyDirectory($source, \Drupal::root() . '/' . PublicStream::basePath());
    }
    catch (FileTransferException $e) {
      $gotit = FALSE;
    }
    $this->assertTrue($gotit, 'Was able to copy a directory inside of the jailed area');
  }
}
