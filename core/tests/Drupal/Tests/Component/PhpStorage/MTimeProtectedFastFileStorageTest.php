<?php

/**
 * @file
 * Definition of Drupal\Tests\Component\PhpStorage\MTimeProtectedFileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the directory mtime based PHP loader implementation.
 */
class MTimeProtectedFastFileStorageTest extends MTimeProtectedFileStorageTest {

  /**
   * The expected test results for the security test.
   *
   * The first iteration does not change the directory mtime so this class will
   * include the hacked file on the first try but the second test will change
   * the directory mtime and so on the second try the file will not be included.
   */
  protected $expected = array(TRUE, FALSE);

  /**
   * Test this class.
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage';

  public static function getInfo() {
    return array(
      'name' => 'MTime protected fast file storage',
      'description' => 'Tests the MTimeProtectedFastFileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }
}
