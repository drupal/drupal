<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\StringTranslation\TranslationManager.
 */
#[CoversClass(TranslationManager::class)]
#[Group('StringTranslation')]
class TranslationManagerTest extends UnitTestCase {

  /**
   * The tested translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->translationManager = new TestTranslationManager();
  }

  /**
   * Provides some test data for formatPlural()
   *
   * @return array
   *   An array of test data for formatPlural().
   */
  public static function providerTestFormatPlural(): array {
    return [
      [1, 'Singular', '@count plural', [], [], 'Singular'],
      [2, 'Singular', '@count plural', [], [], '2 plural'],
      [2, 'Singular', '@count @arg', ['@arg' => '<script>'], [], '2 &lt;script&gt;'],
      [2, 'Singular', '@count %arg', ['%arg' => '<script>'], [], '2 <em class="placeholder">&lt;script&gt;</em>'],
      [1, 'Singular', '@count plural', [], ['langcode' => NULL], 'Singular'],
      [1, 'Singular', '@count plural', [], ['langcode' => 'es'], 'Singular'],
      // The count is documented as int|float, and formatPlural() itself is not
      // typed, so callers also pass numeric strings. A count must never be
      // truncated for display, and only a count that is exactly one may use
      // the singular form. Both forms carry @count here, so that a wrongly
      // selected form and a truncated count are each visible in the result.
      [1, '@count hour', '@count hours', [], [], '1 hour'],
      ['1', '@count hour', '@count hours', [], [], '1 hour'],
      [1.0, '@count hour', '@count hours', [], [], '1 hour'],
      ['1.0', '@count hour', '@count hours', [], [], '1.0 hour'],
      [2, '@count hour', '@count hours', [], [], '2 hours'],
      ['2', '@count hour', '@count hours', [], [], '2 hours'],
      [0.6, '@count hour', '@count hours', [], [], '0.6 hours'],
      ['0.6', '@count hour', '@count hours', [], [], '0.6 hours'],
      [1.5, '@count hour', '@count hours', [], [], '1.5 hours'],
      ['1.5', '@count hour', '@count hours', [], [], '1.5 hours'],
      [-1.5, '@count hour', '@count hours', [], [], '-1.5 hours'],
    ];
  }

  /**
   * Tests format plural.
   */
  #[DataProvider('providerTestFormatPlural')]
  public function testFormatPlural(int|float|string $count, string $singular, string $plural, array $args, array $options, string $expected): void {
    $langcode = empty($options['langcode']) ? 'fr' : $options['langcode'];
    $translator = $this->createMock('\Drupal\Core\StringTranslation\Translator\TranslatorInterface');
    $translator->expects($this->once())
      ->method('getStringTranslation')
      ->with($langcode, $this->anything(), $this->anything())
      ->willReturnCallback(function ($langcode, $string, $context) {
        return $string;
      });
    $this->translationManager->setDefaultLangcode('fr');
    $this->translationManager->addTranslator($translator);
    $result = $this->translationManager->formatPlural($count, $singular, $plural, $args, $options);
    $this->assertSame($expected, (string) $result);
    $this->assertInstanceOf(MarkupInterface::class, $result);
  }

  /**
   * Tests that a non-numeric count is rejected rather than rendered.
   */
  public function testFormatPluralWithNonNumericCount(): void {
    $this->expectException(\TypeError::class);
    $this->translationManager->formatPlural('not a number', 'Singular', '@count plural')->render();
  }

  /**
   * Tests translation using placeholders.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   An associative array of replacements to make after translation.
   * @param string $expected_string
   *   The expected translated string value.
   */
  #[DataProvider('providerTestTranslatePlaceholder')]
  public function testTranslatePlaceholder(string $string, array $args, string $expected_string): void {
    $actual = $this->translationManager->translate($string, $args);
    $this->assertInstanceOf(MarkupInterface::class, $actual);
    $this->assertEquals($expected_string, (string) $actual);
  }

  /**
   * Provides test data for translate().
   *
   * @return array
   *   An array of test data for translate().
   */
  public static function providerTestTranslatePlaceholder(): array {
    return [
      ['foo @bar', ['@bar' => 'bar'], 'foo bar'],
      ['bar %baz', ['%baz' => 'baz'], 'bar <em class="placeholder">baz</em>'],
      ['bar @bar %baz', ['@bar' => 'bar', '%baz' => 'baz'], 'bar bar <em class="placeholder">baz</em>'],
      ['bar %baz @bar', ['%baz' => 'baz', '@bar' => 'bar'], 'bar <em class="placeholder">baz</em> bar'],
    ];
  }

}

/**
 * A chained translation implementation used for testing.
 */
class TestTranslationManager extends TranslationManager {

  public function __construct() {
  }

}
