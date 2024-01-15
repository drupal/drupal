<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Component\Utility\Random;

/**
 * Tests for the UnitTestCase class.
 *
 * @group Tests
 */
class UnitTestCaseTest extends UnitTestCase {

  /**
   * Tests the dump() function in a test run in the same process.
   */
  public function testVarDumpSameProcess() {
    // Append the stream capturer to the STDOUT stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDOUT, "capture");

    // Dump some variables.
    $object = (object) [
      'foo' => 'bar',
    ];
    dump($object);
    dump('banana');

    $this->assertStringContainsString('bar', StreamCapturer::$cache);
    $this->assertStringContainsString('banana', StreamCapturer::$cache);
  }

  /**
   * Tests the dump() function in a test run in a separate process.
   *
   * @runInSeparateProcess
   */
  public function testVarDumpSeparateProcess() {
    // Append the stream capturer to the STDOUT stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDOUT, "capture");

    // Dump some variables.
    $object = (object) [
      'foo' => 'bar',
    ];
    dump($object);
    dump('banana');

    $this->assertStringContainsString('bar', StreamCapturer::$cache);
    $this->assertStringContainsString('banana', StreamCapturer::$cache);
  }

  /**
   * Tests the deprecation of accessing the randomGenerator property directly.
   *
   * @group legacy
   */
  public function testGetRandomGeneratorPropertyDeprecation() {
    $this->expectDeprecation('Accessing the randomGenerator property is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use getRandomGenerator() instead. See https://www.drupal.org/node/3358445');
    // We purposely test accessing an undefined property here. We need to tell
    // PHPStan to ignore that.
    // @phpstan-ignore-next-line
    $this->assertInstanceOf(Random::class, $this->randomGenerator);
  }

}
