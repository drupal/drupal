<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigFileSecurityTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\FileStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the secure file writer.
 */
class ConfigFileSecurityTest extends WebTestBase {
  protected $filename = 'foo.bar';

  protected $testContent = array('greeting' => 'Good morning, Denver!');

  public static function getInfo() {
    return array(
      'name' => 'File security',
      'description' => 'Tests security of saved configuration files.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests that a file written by this system can be successfully read back.
   */
  function testFilePersist() {
    $file = new FileStorage($this->filename);
    $file->write($this->testContent);

    unset($file);

    // Reading should throw an exception in case of bad validation.
    // Note that if any other exception is thrown, we let the test system
    // handle catching and reporting it.
    try {
      $file = new FileStorage($this->filename);
      $saved_content = $file->read();

      $this->assertEqual($saved_content, $this->testContent);
    }
    catch (Exception $e) {
      $this->fail('File failed verification when being read.');
    }
  }
}
