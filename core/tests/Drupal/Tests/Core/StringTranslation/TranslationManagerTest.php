<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\StringTranslation\TranslationManagerTest.
 */

namespace Drupal\Tests\Core\StringTranslation {

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the translation manager.
 *
 * @see \Drupal\Core\StringTranslation\TranslationManager
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
  public static function getInfo() {
    return array(
      'name' => 'Translation manager',
      'description' => 'Tests the translation manager.',
      'group' => 'Translation',
    );
  }

  protected function setUp() {
    $this->translationManager = new TestTranslationManager();
  }

  /**
   * Provides some test data for formatPlural()
   * @return array
   */
  public function providerTestFormatPlural() {
    return array(
      array(1, 'Singular', '@count plural', array(), array(), 'Singular'),
      array(2, 'Singular', '@count plural', array(), array(), '2 plural'),
      // @todo support locale_get_plural
      array(2, 'Singular', '@count plural @arg', array('@arg' => 3), array(), '2 plural 3'),
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
  }

}

class TestTranslationManager extends TranslationManager {

  public function __construct() {
  }

}

}

namespace {
  if (!defined('LOCALE_PLURAL_DELIMITER')) {
    define('LOCALE_PLURAL_DELIMITER', "\03");
  }
}
