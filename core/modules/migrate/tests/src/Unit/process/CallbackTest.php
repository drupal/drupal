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
   * Tests callback with valid "callable".
   *
   * @dataProvider providerCallback
   */
  public function testCallback($callable) {
    $configuration = ['callable' => $callable];
    $this->plugin = new Callback($configuration, 'map', []);
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('foobar', $value);
  }

  /**
   * Data provider for ::testCallback().
   */
  public function providerCallback() {
    return [
      'function' => ['strtolower'],
      'class method' => [[self::class, 'strtolower']],
    ];
  }

  /**
   * Tests callback with valid "callable" and multiple arguments.
   *
   * @dataProvider providerCallbackArray
   */
  public function testCallbackArray($callable, $args, $result) {
    $configuration = ['callable' => $callable, 'unpack_source' => TRUE];
    $this->plugin = new Callback($configuration, 'map', []);
    $value = $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($result, $value);
  }

  /**
   * Data provider for ::testCallbackArray().
   */
  public function providerCallbackArray() {
    return [
      'date format' => [
        'date',
        ['Y-m-d', 995328000],
        '2001-07-17',
      ],
      'rtrim' => [
        'rtrim',
        ['https://www.example.com/', '/'],
        'https://www.example.com',
      ],
      'str_replace' => [
        'str_replace',
        [['One', 'two'], ['1', '2'], 'One, two, three!'],
        '1, 2, three!',
      ],
    ];
  }

  /**
   * Tests callback exceptions.
   *
   * @dataProvider providerCallbackExceptions
   */
  public function testCallbackExceptions($message, $configuration) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $this->plugin = new Callback($configuration, 'map', []);
  }

  /**
   * Data provider for ::testCallbackExceptions().
   */
  public function providerCallbackExceptions() {
    return [
      'not set' => [
        'message' => 'The "callable" must be set.',
        'configuration' => [],
      ],
      'invalid method' => [
        'message' => 'The "callable" must be a valid function or method.',
        'configuration' => ['callable' => 'nonexistent_callable'],
      ],
    ];
  }

  /**
   * Makes a string lowercase for testing purposes.
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   The lowercased string.
   *
   * @see \Drupal\Tests\migrate\Unit\process\CallbackTest::providerCallback()
   */
  public static function strToLower($string) {
    return mb_strtolower($string);
  }

}
