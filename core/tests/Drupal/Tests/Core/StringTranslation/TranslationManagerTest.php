<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\StringTranslation\TranslationManagerTest.
 */

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\StringTranslation\TranslationManager
 * @group StringTranslation
 */
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
    $this->translationManager = new TestTranslationManager();
  }

  /**
   * Provides some test data for formatPlural()
   * @return array
   */
  public function providerTestFormatPlural() {
    return [
      [1, 'Singular', '@count plural', [], [], 'Singular'],
      [2, 'Singular', '@count plural', [], [], '2 plural'],
      // @todo support locale_get_plural
      [2, 'Singular', '@count @arg', ['@arg' => '<script>'], [], '2 &lt;script&gt;'],
      [2, 'Singular', '@count %arg', ['%arg' => '<script>'], [], '2 <em class="placeholder">&lt;script&gt;</em>'],
      [1, 'Singular', '@count plural', [], ['langcode' => NULL], 'Singular'],
      [1, 'Singular', '@count plural', [], ['langcode' => 'es'], 'Singular'],
    ];
  }

  /**
   * @dataProvider providerTestFormatPlural
   */
  public function testFormatPlural($count, $singular, $plural, array $args, array $options, $expected) {
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
    $this->assertEquals($expected, $result);
    $this->assertInstanceOf(MarkupInterface::class, $result);
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
   *
   * @dataProvider providerTestTranslatePlaceholder
   */
  public function testTranslatePlaceholder($string, array $args, $expected_string) {
    $actual = $this->translationManager->translate($string, $args);
    $this->assertInstanceOf(MarkupInterface::class, $actual);
    $this->assertEquals($expected_string, (string) $actual);
  }

  /**
   * Provides test data for translate().
   *
   * @return array
   */
  public function providerTestTranslatePlaceholder() {
    return [
      ['foo @bar', ['@bar' => 'bar'], 'foo bar'],
      ['bar %baz', ['%baz' => 'baz'], 'bar <em class="placeholder">baz</em>'],
      ['bar @bar %baz', ['@bar' => 'bar', '%baz' => 'baz'], 'bar bar <em class="placeholder">baz</em>'],
      ['bar %baz @bar', ['%baz' => 'baz', '@bar' => 'bar'], 'bar <em class="placeholder">baz</em> bar'],
    ];
  }

}

class TestTranslationManager extends TranslationManager {

  public function __construct() {
  }

}
