<?php

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
  public function testFindFile() {
    $tmp = MockFileFinder::create('testfilename.txt');
    $this->assertEquals('testfilename.txt', $tmp->findFile('n/a'));
    $this->assertEquals('testfilename.txt', $tmp->findFile('SomeClass'));
  }

}
