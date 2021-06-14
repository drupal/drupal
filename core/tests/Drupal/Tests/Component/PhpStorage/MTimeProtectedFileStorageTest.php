<?php

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the MTimeProtectedFileStorage implementation.
 *
 * @coversDefaultClass \Drupal\Component\PhpStorage\MTimeProtectedFileStorage
 *
 * @group Drupal
 * @group PhpStorage
 */
class MTimeProtectedFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The default implementation protects against even the filemtime change so
   * both iterations will return FALSE.
   */
  protected $expected = [FALSE, FALSE];

  /**
   * The PHP storage class to test.
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';

}
