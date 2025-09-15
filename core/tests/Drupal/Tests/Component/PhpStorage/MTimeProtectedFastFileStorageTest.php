<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;

/**
 * Tests the MTimeProtectedFastFileStorage implementation.
 */
#[CoversClass(MTimeProtectedFastFileStorage::class)]
#[Group('Drupal')]
#[Group('PhpStorage')]
#[Medium]
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
