<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
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
   * Test callback with valid "callable" and multiple arguments.
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
      'pi' => [
        'pi',
        [],
        pi(),
      ],
    ];
  }

  /**
   * Tests callback exceptions.
   *
   * @param string $message
   *   The expected exception message.
   * @param array $configuration
   *   The plugin configuration being tested.
   * @param string $class
   *   (optional) The expected exception class.
   * @param mixed $args
   *   (optional) Arguments to pass to the transform() method.
   *
   * @dataProvider providerCallbackExceptions
   */
  public function testCallbackExceptions($message, array $configuration, $class = 'InvalidArgumentException', $args = NULL) {
    $this->expectException($class);
    $this->expectExceptionMessage($message);
    $this->plugin = new Callback($configuration, 'map', []);
    $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
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
      'array required' => [
        'message' => "When 'unpack_source' is set, the source must be an array. Instead it was of type 'string'",
        'configuration' => ['callable' => 'count', 'unpack_source' => TRUE],
        'class' => MigrateException::class,
        'args' => 'This string is not an array.',
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
