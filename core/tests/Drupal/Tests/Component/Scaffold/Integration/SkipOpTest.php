<?php

namespace Drupal\Tests\Component\Scaffold\Integration;

use Drupal\Component\Scaffold\Operations\SkipOp;
use Drupal\Component\Scaffold\ScaffoldOptions;
use Drupal\Tests\Component\Scaffold\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Scaffold\Operations\SkipOp
 *
 * @group Scaffold
 */
class SkipOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new SkipOp();
    // Assert that there is no target file before we run our test.
    $this->assertFileNotExists($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was not created.
    $this->assertFileNotExists($destination->fullPath());
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertContains('Skip [web-root]/robots.txt: disabled', $output);
  }

}
