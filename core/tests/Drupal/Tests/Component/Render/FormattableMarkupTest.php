<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Render;

// cspell:ignore errfile errline

use Drupal\Component\Render\FormattableMarkup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the TranslatableMarkup class.
 */
#[CoversClass(FormattableMarkup::class)]
#[Group('utility')]
class FormattableMarkupTest extends TestCase {

  /**
   * The error message of the last error in the error handler.
   *
   * @var string
   */
  protected $lastErrorMessage;

  /**
   * The error number of the last error in the error handler.
   *
   * @var int
   */
  protected $lastErrorNumber;

  /**
   * @legacy-covers ::__toString
   * @legacy-covers ::jsonSerialize
   */
  public function testToString(): void {
    $string = 'Can I have a @replacement';
    $formattable_string = new FormattableMarkup($string, ['@replacement' => 'kitten']);
    $text = (string) $formattable_string;
    $this->assertEquals('Can I have a kitten', $text);
    $text = $formattable_string->jsonSerialize();
    $this->assertEquals('Can I have a kitten', $text);
  }

  /**
   * Tests count.
   */
  public function testCount(): void {
    $string = 'Can I have a @replacement';
    $formattable_string = new FormattableMarkup($string, ['@replacement' => 'kitten']);
    $this->assertEquals(strlen($string), $formattable_string->count());
  }

  /**
   * @legacy-covers ::__toString
   */
  #[DataProvider('providerTestUnexpectedPlaceholder')]
  public function testUnexpectedPlaceholder(string $string, array $arguments, ?int $error_number, string $error_message): void {
    // We set a custom error handler that saves the last error.
    // We need this custom error handler because we cannot rely on the error to
    // exception conversion as __toString is never allowed to leak any kind of
    // exception.
    // https://github.com/sebastianbergmann/phpunit/issues/487
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
      $this->lastErrorNumber = $errno;
      $this->lastErrorMessage = $errstr;
      return TRUE;
    });
    // We want this to trigger an error.
    $markup = new FormattableMarkup($string, $arguments);
    // Cast it to a string which will generate the errors.
    $output = (string) $markup;
    restore_error_handler();
    // The string should not change.
    $this->assertEquals($string, $output);
    $this->assertEquals($error_number, $this->lastErrorNumber);
    $this->assertEquals($error_message, $this->lastErrorMessage);
  }

  /**
   * Data provider for FormattableMarkupTest::testUnexpectedPlaceholder().
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerTestUnexpectedPlaceholder(): array {
    return [
      [
        'Non alpha, non-allowed starting character: ~placeholder',
        ['~placeholder' => 'replaced'],
        E_USER_WARNING,
        'Placeholders must begin with one of the following "@", ":" or "%", invalid placeholder (~placeholder) with string: "Non alpha, non-allowed starting character: ~placeholder"',
      ],
      [
        'Alpha starting character: placeholder',
        ['placeholder' => 'replaced'],
        NULL,
        '',
      ],
      // Ensure that where the placeholder is located in the string is
      // irrelevant.
      ['placeholder', ['placeholder' => 'replaced'], NULL, ''],
      ['No replacements', ['foo' => 'bar'], NULL, ''],
    ];
  }

}
