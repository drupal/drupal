<?php

namespace Drupal\Tests\Component\Scaffold\Integration;

use Drupal\Component\Scaffold\Operations\ReplaceOp;
use Drupal\Component\Scaffold\ScaffoldOptions;
use Drupal\Tests\Component\Scaffold\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Scaffold\Operations\ReplaceOp
 *
 * @group Scaffold
 */
class ReplaceOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $source = $fixtures->sourcePath('drupal-assets-fixture', 'robots.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new ReplaceOp($source, TRUE);
    // Assert that there is no target file before we run our test.
    $this->assertFileNotExists($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold file.
    $contents = trim(file_get_contents($destination->fullPath()));
    $this->assertEquals('# Test version of robots.txt from drupal/core.', $contents);
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertContains('Copy [web-root]/robots.txt from assets/robots.txt', $output);
  }

}
