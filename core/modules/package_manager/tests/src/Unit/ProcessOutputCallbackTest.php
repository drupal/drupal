<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\package_manager\ProcessOutputCallback;
use Drupal\Tests\UnitTestCase;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * @covers \Drupal\package_manager\ProcessOutputCallback
 * @group package_manager
 */
class ProcessOutputCallbackTest extends UnitTestCase {

  /**
   * Tests what happens when the output buffer has invalid JSON.
   */
  public function testInvalidJson(): void {
    $callback = new ProcessOutputCallback();
    $callback(OutputTypeEnum::OUT, '{A string of invalid JSON! 😈');

    $this->expectException(\JsonException::class);
    $this->expectExceptionMessage('Syntax error');
    $callback->parseJsonOutput();
  }

  /**
   * Tests what happens when there is error output only.
   */
  public function testErrorOutputOnly(): void {
    $callback = new ProcessOutputCallback();
    $logger = new TestLogger();
    $callback->setLogger($logger);

    $error_text = 'What happened?';
    $callback(OutputTypeEnum::ERR, $error_text);

    $this->assertSame([$error_text], $callback->getErrorOutput());
    // The error should not yet be logged.
    $this->assertEmpty($logger->records);

    // There should be no output data, but calling getOutput() should log the
    // error.
    $this->assertSame([], $callback->getOutput());
    $this->assertNull($callback->parseJsonOutput());
    $this->assertTrue($logger->hasWarning($error_text));

    // Resetting the callback should clear the error buffer but the log should
    // still have the error from before.
    $callback->reset();
    $this->assertTrue($logger->hasWarning($error_text));
  }

  /**
   * Tests the full lifecycle of a ProcessOutputCallback object.
   */
  public function testCallback(): void {
    $callback = new ProcessOutputCallback();
    $logger = new TestLogger();
    $callback->setLogger($logger);

    // The buffers should initially be empty, and nothing should be logged.
    $this->assertSame([], $callback->getOutput());
    $this->assertSame([], $callback->getErrorOutput());
    $this->assertNull($callback->parseJsonOutput());
    $this->assertEmpty($logger->records);

    // Send valid JSON data to the callback, one line at a time.
    $data = [
      'value' => 'I have value!',
      'another value' => 'I have another value!',
      'one' => 1,
    ];
    $json = json_encode($data, JSON_PRETTY_PRINT);
    // Ensure the JSON is a multi-line string.
    $this->assertGreaterThan(1, substr_count($json, "\n"));
    $expected_output = [];
    foreach (explode("\n", $json) as $line) {
      $callback(OutputTypeEnum::OUT, "$line\n");
      $expected_output[] = "$line\n";
    }
    $this->assertSame($expected_output, $callback->getOutput());
    // Ensure that parseJsonOutput() can parse the data without errors.
    $this->assertSame($data, $callback->parseJsonOutput());
    $this->assertSame([], $callback->getErrorOutput());
    $this->assertEmpty($logger->records);

    // If we send error output, it should be logged, but we should still be able
    // to get the data we already sent.
    $callback(OutputTypeEnum::ERR, 'Oh no, what happened?');
    $callback(OutputTypeEnum::ERR, 'Really what happened?!');
    $this->assertSame($data, $callback->parseJsonOutput());
    $expected_error = ['Oh no, what happened?', 'Really what happened?!'];
    $this->assertSame($expected_error, $callback->getErrorOutput());
    $this->assertTrue($logger->hasWarning('Oh no, what happened?Really what happened?!'));

    // Send more output and error data to the callback; they should be appended
    // to the data we previously sent.
    $callback(OutputTypeEnum::OUT, '{}');
    $expected_output[] = '{}';
    $callback(OutputTypeEnum::ERR, 'new Error 1!');
    $callback(OutputTypeEnum::ERR, 'new Error 2!');
    $expected_error[] = 'new Error 1!';
    $expected_error[] = 'new Error 2!';
    // The output buffer will no longer be valid JSON, so don't try to parse it.
    $this->assertSame($expected_output, $callback->getOutput());
    $this->assertSame($expected_error, $callback->getErrorOutput());
    $this->assertTrue($logger->hasWarning(implode('', $expected_error)));
    // The previously logged error output should still be there.
    $this->assertTrue($logger->hasWarning('Oh no, what happened?Really what happened?!'));

    // Clear all stored output and errors.
    $callback->reset();
    $this->assertSame([], $callback->getOutput());
    $this->assertSame([], $callback->getErrorOutput());
    $this->assertNull($callback->parseJsonOutput());

    // Send more output and error data.
    $callback(OutputTypeEnum::OUT, 'Bonjour!');
    $callback(OutputTypeEnum::ERR, 'You continue to annoy me.');
    // We should now only see the stuff we just sent...
    $this->assertSame(['Bonjour!'], $callback->getOutput());
    $this->assertSame(['You continue to annoy me.'], $callback->getErrorOutput());
    $this->assertTrue($logger->hasWarning('You continue to annoy me.'));
    // ...but the previously logged errors should still be there.
    $this->assertTrue($logger->hasWarning('Oh no, what happened?Really what happened?!new Error 1!new Error 2!'));
    $this->assertTrue($logger->hasWarning('Oh no, what happened?Really what happened?!'));
  }

}
