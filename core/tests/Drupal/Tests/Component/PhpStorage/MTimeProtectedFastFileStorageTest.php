<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the MTimeProtectedFastFileStorage implementation.
 *
 * @coversDefaultClass \Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage
 *
 * @group Drupal
 * @group PhpStorage
 */
class MTimeProtectedFastFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The first iteration does not change the directory mtime so this class will
   * include the hacked file on the first try but the second test will change
   * the directory mtime and so on the second try the file will not be included.
   *
   * @var bool[]
   */
  protected array $expected = [TRUE, FALSE];

  /**
   * The PHP storage class to test.
   *
   * @var class-string
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage';

}
