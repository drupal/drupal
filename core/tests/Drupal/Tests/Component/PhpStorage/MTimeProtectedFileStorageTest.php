<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\MTimeProtectedFileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the directory mtime based PHP loader implementation.
 */
class MTimeProtectedFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The default implementation protects against even the filemtime change so
   * both iterations will return FALSE.
   */
  protected $expected = array(FALSE, FALSE);

  /**
   * The PHP storage class to test.
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'MTime protected file storage',
      'description' => 'Tests the MTimeProtectedFileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }

}
