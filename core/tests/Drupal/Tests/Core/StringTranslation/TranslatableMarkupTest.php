<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TranslatableMarkup class.
 */
#[CoversClass(TranslatableMarkup::class)]
#[Group('StringTranslation')]
class TranslatableMarkupTest extends UnitTestCase {

  /**
   * Tests that errors are correctly handled when a __toString() fails.
   */
  public function testToString(): void {
    $string = 'May I have an exception?';
    $exception = new \Exception('Yes you may.');
    $text = $this->getMockBuilder(TranslatableMarkup::class)
      ->setConstructorArgs([$string, [], []])
      ->onlyMethods(['render'])
      ->getMock();
    $text
      ->expects($this->once())
      ->method('render')
      ->willThrowException($exception);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageIs($exception->getMessage());
    (string) $text;
  }

  // phpcs:disable Drupal.Semantics.FunctionT.NotLiteralString

  /**
   * Tests exception when a string is passed.
   */
  public function testIsStringAssertion(): void {
    $translation = $this->getStringTranslationStub();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageIs('$string ("foo") must be a string.');
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    // @phpstan-ignore argument.type
    new TranslatableMarkup(new TranslatableMarkup('foo', [], [], $translation));
  }

  /**
   * Tests exception when a FormattableMarkup is passed.
   */
  public function testIsStringAssertionWithFormattableMarkup(): void {
    $formattable_string = new FormattableMarkup('@bar', ['@bar' => 'foo']);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageIs('$string ("foo") must be a string.');
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    // @phpstan-ignore argument.type
    new TranslatableMarkup($formattable_string);
  }

  // phpcs:enable

}
