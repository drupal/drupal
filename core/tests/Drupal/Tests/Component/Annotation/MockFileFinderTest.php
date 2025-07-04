<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\Reflection\MockFileFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Annotation\Reflection\MockFileFinder.
 */
#[CoversClass(MockFileFinder::class)]
#[Group('Annotation')]
class MockFileFinderTest extends TestCase {

  /**
   * @legacy-covers ::create
   * @legacy-covers ::findFile
   */
  public function testFindFile(): void {
    $tmp = MockFileFinder::create('test_filename.txt');
    $this->assertEquals('test_filename.txt', $tmp->findFile('n/a'));
    $this->assertEquals('test_filename.txt', $tmp->findFile('SomeClass'));
  }

}
