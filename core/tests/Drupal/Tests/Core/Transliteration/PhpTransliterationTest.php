<?php

namespace Drupal\Tests\Core\Transliteration;

use Drupal\Component\Utility\Random;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Transliteration component functionality.
 *
 * @group Transliteration
 *
 * @coversClass \Drupal\Core\Transliteration\PhpTransliteration
 */
class PhpTransliterationTest extends UnitTestCase {

  /**
   * Tests the PhpTransliteration with an alter hook.
   *
   * @param string $langcode
   *   The langcode of the string.
   * @param string $original
   *   The string which was not transliterated yet.
   * @param string $expected
   *   The string expected after the transliteration.
   * @param string|null $printable
   *   (optional) An alternative version of the original string which is
   *   printable in the output.
   *
   * @dataProvider providerTestPhpTransliterationWithAlter
   */
  public function testPhpTransliterationWithAlter($langcode, $original, $expected, $printable = NULL) {
    if ($printable === NULL) {
      $printable = $original;
    }

    // Test each case both with a new instance of the transliteration class,
    // and with one that builds as it goes.
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('alter')
      ->will($this->returnCallback(function($hook, &$overrides, $langcode) {
        if ($langcode == 'zz') {
          // The default transliteration of Ä is A, but change it to Z for testing.
          $overrides[0xC4] = 'Z';
          // Also provide transliterations of two 5-byte characters from
          // http://wikipedia.org/wiki/Gothic_alphabet.
          $overrides[0x10330] = 'A';
          $overrides[0x10338] = 'Th';
        }
      }));
    $transliteration = new PhpTransliteration(NULL, $module_handler);

    $actual = $transliteration->transliterate($original, $langcode);
    $this->assertSame($expected, $actual, "'$printable' transliteration to '$actual' is identical to '$expected' for language '$langcode' in service instance.");
  }

  /**
   * Provides test data for testPhpTransliterationWithAlter.
   *
   * @return array
   */
  public function providerTestPhpTransliterationWithAlter() {
    $random_generator = new Random();
    $random = $random_generator->string(10);
    // Make some strings with two, three, and four-byte characters for testing.
    // Note that the 3-byte character is overridden by the 'kg' language.
    $two_byte = 'Ä Ö Ü Å Ø äöüåøhello';
    // These are two Gothic alphabet letters. See
    // http://wikipedia.org/wiki/Gothic_alphabet
    // They are not in our tables, but should at least give us '?' (unknown).
    $five_byte = html_entity_decode('&#x10330;&#x10338;', ENT_NOQUOTES, 'UTF-8');
    // Five-byte characters do not work in MySQL, so make a printable version.
    $five_byte_printable = '&#x10330;&#x10338;';

    $cases = array(
      // Test the language override hook in the test module, which changes
      // the transliteration of Ä to Z and provides for the 5-byte characters.
      array('zz', $two_byte, 'Z O U A O aouaohello'),
      array('zz', $random, $random),
      array('zz', $five_byte, 'ATh', $five_byte_printable),
    );

    return $cases;
  }

}
