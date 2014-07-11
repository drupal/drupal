<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\FolderTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * This test will check SimpleTest's treatment of hook_install during setUp. 
 * Image module is used for test.
 *
 * @group simpletest
 */
class FolderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image');

  function testFolderSetup() {
    $directory = file_default_scheme() . '://styles';
    $this->assertTrue(file_prepare_directory($directory, FALSE), 'Directory created.');
  }
}
