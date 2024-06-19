<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\Reflection\MockFileFinder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Reflection\MockFileFinder
 * @group Annotation
 */
class MockFileFinderTest extends TestCase {

  /**
   * @covers ::create
   * @covers ::findFile
   */
  public function testFindFile(): void {
    $tmp = MockFileFinder::create('test_filename.txt');
    $this->assertEquals('test_filename.txt', $tmp->findFile('n/a'));
    $this->assertEquals('test_filename.txt', $tmp->findFile('SomeClass'));
  }

}
