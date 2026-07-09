<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Scaffold\Integration;

use Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp.
 */
#[CoversClass(ReplaceOp::class)]
#[Group('Scaffold')]
class ReplaceOpTest extends TestCase {

  /**
   * Tests process.
   */
  public function testProcess(): void {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $source = $fixtures->sourcePath('drupal-assets-fixture', 'robots.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new ReplaceOp($source, TRUE);
    // Assert that there is no target file before we run our test.
    $this->assertFileDoesNotExist($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold file.
    $contents = trim(file_get_contents($destination->fullPath()));
    $this->assertEquals('# Test version of robots.txt from drupal/core.', $contents);
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Copy [web-root]/robots.txt from assets/robots.txt', $output);

    // Test when the target file is already present and not writable.
    $this->assertDirectoryIsWritable(dirname($destination->fullPath()));
    file_put_contents($destination->fullPath(), 'This is a test');
    chmod($destination->fullPath(), 0444);
    $this->assertFileIsNotWritable($destination->fullPath());
    chmod(dirname($destination->fullPath()), 0555);
    $this->assertDirectoryIsNotWritable(dirname($destination->fullPath()));
    $sut = new ReplaceOp($source, TRUE);
    $this->assertFileExists($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(TRUE), $options);
    // Assert the target contained the contents from the correct scaffold file.
    $contents = trim(file_get_contents($destination->fullPath()));
    $this->assertEquals('# Test version of robots.txt from drupal/core.', $contents);
    // Assert the target is still not writable.
    $this->assertFileIsNotWritable($destination->fullPath());
    $this->assertDirectoryIsNotWritable(dirname($destination->fullPath()));
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Copy [web-root]/robots.txt from assets/robots.txt', $output);

    // Make it a symlink and ensure that the permissions code is not executed.
    $source_perms = fileperms($source->fullPath());
    $this->assertNotEquals($source_perms, fileperms($destination->fullPath()));
    $options = ScaffoldOptions::create(['drupal-scaffold' => ['symlink' => TRUE]]);
    $sut->process($destination, $fixtures->io(TRUE), $options);
    $this->assertTrue(is_link($destination->fullPath()));
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Link [web-root]/robots.txt', $output);
    $this->assertEquals($source_perms, fileperms($destination->fullPath()));
    $this->assertEquals($source_perms, fileperms($source->fullPath()));
  }

  /**
   * Tests empty file.
   *
   * @legacy-covers ::process
   */
  public function testEmptyFile(): void {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/empty_file.txt');
    $source = $fixtures->sourcePath('empty-file', 'empty_file.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new ReplaceOp($source, TRUE);
    // Assert that there is no target file before we run our test.
    $this->assertFileDoesNotExist($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold file.
    $this->assertSame('', file_get_contents($destination->fullPath()));
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Copy [web-root]/empty_file.txt from assets/empty_file.txt', $output);
  }

}
