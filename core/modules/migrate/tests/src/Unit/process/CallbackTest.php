<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Callback;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the callback process plugin.
 */
#[Group('migrate')]
class CallbackTest extends MigrateProcessTestCase {

  /**
   * Tests callback with valid "callable".
   */
  #[DataProvider('providerCallback')]
  public function testCallback(string|array $callable): void {
    $configuration = ['callable' => $callable];
    $this->plugin = new Callback($configuration, 'map', []);
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('foobar', $value);
  }

  /**
   * Data provider for ::testCallback().
   */
  public static function providerCallback(): array {
    return [
      'function' => ['strtolower'],
      'class method' => [[self::class, 'strtolower']],
    ];
  }

  /**
   * Test callback with valid "callable" and multiple arguments.
   */
  #[DataProvider('providerCallbackArray')]
  public function testCallbackArray(string $callable, array $args, string|float $result): void {
    $configuration = ['callable' => $callable, 'unpack_source' => TRUE];
    $this->plugin = new Callback($configuration, 'map', []);
    $value = $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($result, $value);
  }

  /**
   * Data provider for ::testCallbackArray().
   */
  public static function providerCallbackArray(): array {
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
   */
  #[DataProvider('providerCallbackExceptions')]
  public function testCallbackExceptions(string $message, array $configuration, string $class = 'InvalidArgumentException', ?string $args = NULL): void {
    $this->expectException($class);
    $this->expectExceptionMessageIs($message);
    $this->plugin = new Callback($configuration, 'map', []);
    $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Data provider for ::testCallbackExceptions().
   */
  public static function providerCallbackExceptions(): array {
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
  public static function strToLower($string): string {
    return mb_strtolower($string);
  }

}
