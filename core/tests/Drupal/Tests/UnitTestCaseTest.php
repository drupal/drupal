<?php

declare(strict_types=1);

namespace Drupal\Tests;

/**
 * Tests for the UnitTestCase class.
 *
 * @group Tests
 */
class UnitTestCaseTest extends UnitTestCase {

  /**
   * Tests the dump() function in a test run in the same process.
   */
  public function testVarDumpSameProcess(): void {
    // Append the stream capturer to the STDERR stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDERR, "capture");

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
  public function testVarDumpSeparateProcess(): void {
    // Append the stream capturer to the STDERR stream, so that we can test the
    // dump() output and also prevent it from actually outputting in this
    // particular test.
    stream_filter_register("capture", StreamCapturer::class);
    stream_filter_append(STDERR, "capture");

    // Dump some variables.
    $object = (object) [
      'foo' => 'bar',
    ];
    dump($object);
    dump('banana');

    $this->assertStringContainsString('bar', StreamCapturer::$cache);
    $this->assertStringContainsString('banana', StreamCapturer::$cache);
  }

}
