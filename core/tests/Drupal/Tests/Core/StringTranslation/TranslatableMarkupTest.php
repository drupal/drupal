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
   *
   * @legacy-covers ::__toString
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
    $this->expectExceptionMessage($exception->getMessage());
    (string) $text;
  }

  /**
   * Tests is string assertion.
   *
   * @legacy-covers ::__construct
   */
  public function testIsStringAssertion(): void {
    $translation = $this->getStringTranslationStub();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$string ("foo") must be a string.');
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    new TranslatableMarkup(new TranslatableMarkup('foo', [], [], $translation));
  }

  /**
   * Tests is string assertion with formattable markup.
   *
   * @legacy-covers ::__construct
   */
  public function testIsStringAssertionWithFormattableMarkup(): void {
    $formattable_string = new FormattableMarkup('@bar', ['@bar' => 'foo']);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$string ("foo") must be a string.');
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    new TranslatableMarkup($formattable_string);
  }

}
