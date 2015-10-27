<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\StringTranslation\TranslationManagerTest.
 */

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Component\Utility\SafeMarkup;
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
  protected function setUp() {
    $this->translationManager = new TestTranslationManager();
  }

  /**
   * Provides some test data for formatPlural()
   * @return array
   */
  public function providerTestFormatPlural() {
    return array(
      [1, 'Singular', '@count plural', array(), array(), 'Singular'],
      [2, 'Singular', '@count plural', array(), array(), '2 plural'],
      // @todo support locale_get_plural
      [2, 'Singular', '@count @arg', array('@arg' => '<script>'), array(), '2 &lt;script&gt;'],
      [2, 'Singular', '@count %arg', array('%arg' => '<script>'), array(), '2 <em class="placeholder">&lt;script&gt;</em>'],
    );
  }

  /**
   * @dataProvider providerTestFormatPlural
   */
  public function testFormatPlural($count, $singular, $plural, array $args = array(), array $options = array(), $expected) {
    $translator = $this->getMock('\Drupal\Core\StringTranslation\Translator\TranslatorInterface');
    $translator->expects($this->once())
      ->method('getStringTranslation')
      ->will($this->returnCallback(function ($langcode, $string) {
        return $string;
      }));
    $this->translationManager->addTranslator($translator);
    $result = $this->translationManager->formatPlural($count, $singular, $plural, $args, $options);
    $this->assertEquals($expected, $result);
    $this->assertTrue(SafeMarkup::isSafe($result));
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
  public function testTranslatePlaceholder($string, array $args = array(), $expected_string) {
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
