<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Callback;

/**
 * Tests the callback process plugin.
 *
 * @group migrate
 */
class CallbackTest extends MigrateProcessTestCase {

  /**
   * Test callback with valid "callable".
   *
   * @dataProvider providerCallback
   */
  public function testCallback($callable) {
    $configuration = ['callable' => $callable];
    $this->plugin = new Callback($configuration, 'map', []);
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('foobar', $value);
  }

  /**
   * Data provider for ::testCallback().
   */
  public function providerCallback() {
    return [
      'function' => ['strtolower'],
      'class method' => [['\Drupal\Component\Utility\Unicode', 'strtolower']],
    ];
  }

  /**
   * Test callback excpetions.
   *
   * @dataProvider providerCallbackExceptions
   */
  public function testCallbackExceptions($message, $configuration) {
    $this->setExpectedException(\InvalidArgumentException::class, $message);
    $this->plugin = new Callback($configuration, 'map', []);
  }

  /**
   * Data provider for ::testCallbackExceptions().
   */
  public function providerCallbackExceptions() {
    return [
      'not set' => [
        'message' => 'The "callable" must be set.',
        'configuration' => []
      ],
      'invalid method' => [
        'message' => 'The "callable" must be a valid function or method.',
        'configuration' => ['callable' => 'nonexistent_callable']
      ],
    ];
  }

}
