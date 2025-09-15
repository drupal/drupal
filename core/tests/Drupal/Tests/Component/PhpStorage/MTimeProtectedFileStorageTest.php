<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\MTimeProtectedFileStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;

/**
 * Tests the MTimeProtectedFileStorage implementation.
 */
#[CoversClass(MTimeProtectedFileStorage::class)]
#[Group('Drupal')]
#[Group('PhpStorage')]
#[Medium]
class MTimeProtectedFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The default implementation protects against even the filemtime change so
   * both iterations will return FALSE.
   *
   * @var bool[]
   */
  protected array $expected = [FALSE, FALSE];

  /**
   * The PHP storage class to test.
   *
   * @var class-string
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';

}
