<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Integration;

use Drupal\Composer\Plugin\Scaffold\Operations\AppendOp;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\Scaffold\Operations\AppendOp
 *
 * @group Scaffold
 */
class AppendOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $options = ScaffoldOptions::create([]);
    // Assert that there is no target file before we run our test.
    $this->assertFileNotExists($destination->fullPath());

    // Create a file.
    file_put_contents($destination->fullPath(), "# This is a test\n");

    $prepend = $fixtures->sourcePath('drupal-drupal-test-append', 'prepend-to-robots.txt');
    $append = $fixtures->sourcePath('drupal-drupal-test-append', 'append-to-robots.txt');
    $sut = new AppendOp($prepend, $append);

    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold files.
    $contents = trim(file_get_contents($destination->fullPath()));
    $expected = <<<EOT
# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-test-append composer.json fixture.
# This content is prepended to the top of the existing robots.txt fixture.
# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

# This is a test

# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
# This content is appended to the bottom of the existing robots.txt fixture.
# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-test-append composer.json fixture.
EOT;
    $this->assertEquals(trim($expected), $contents);
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertContains('Prepend to [web-root]/robots.txt from assets/prepend-to-robots.txt', $output);
    $this->assertContains('Append to [web-root]/robots.txt from assets/append-to-robots.txt', $output);
  }

}
