<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Transliteration\TransliterationTest.
 */

namespace Drupal\system\Tests\Transliteration;

use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests Transliteration component functionality.
 *
 * @group Transliteration
 */
class TransliterationTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('transliterate_test');

  /**
   * Tests the PhpTransliteration class.
   */
  public function testPhpTransliteration() {
    $random = $this->randomMachineName(10);
    // Make some strings with two, three, and four-byte characters for testing.
    // Note that the 3-byte character is overridden by the 'kg' language.
    $two_byte = 'Ä Ö Ü Å Ø äöüåøhello';
    // This is a Cyrrillic character that looks something like a u. See
    // http://www.unicode.org/charts/PDF/U0400.pdf
    $three_byte = html_entity_decode('&#x446;', ENT_NOQUOTES, 'UTF-8');
    // This is a Canadian Aboriginal character like a triangle. See
    // http://www.unicode.org/charts/PDF/U1400.pdf
    $four_byte = html_entity_decode('&#x1411;', ENT_NOQUOTES, 'UTF-8');
    // These are two Gothic alphabet letters. See
    // http://en.wikipedia.org/wiki/Gothic_alphabet
    // They are not in our tables, but should at least give us '?' (unknown).
    $five_byte = html_entity_decode('&#x10330;&#x10338;', ENT_NOQUOTES, 'UTF-8');
    // Five-byte characters do not work in MySQL, so make a printable version.
    $five_byte_printable = '&#x10330;&#x10338;';

    $cases = array(
      // Each test case is (language code, input, output).
      // Test ASCII in English.
      array('en', $random, $random),
      // Test ASCII in some other language with no overrides.
      array('fr', $random, $random),
      // Test 3 and 4-byte characters in a language without overrides.
      // Note: if the data tables change, these will need to change too! They
      // are set up to test that data table loading works, so values come
      // directly from the data files.
      array('fr', $three_byte, 'c'),
      array('fr', $four_byte, 'wii'),
      // Test 5-byte characters.
      array('en', $five_byte, '??', $five_byte_printable),
      // Test a language with no overrides.
      array('en', $two_byte, 'A O U A O aouaohello'),
      // Test language overrides provided by core.
      array('de', $two_byte, 'Ae Oe Ue A O aeoeueaohello'),
      array('de', $random, $random),
      array('dk', $two_byte, 'A O U Aa Oe aouaaoehello'),
      array('dk', $random, $random),
      array('kg', $three_byte, 'ts'),
      // Test the language override hook in the test module, which changes
      // the transliteration of Ä to Z and provides for the 5-byte characters.
      array('zz', $two_byte, 'Z O U A O aouaohello'),
      array('zz', $random, $random),
      array('zz', $five_byte, 'ATh', $five_byte_printable),
      // Test strings in some other languages.
      // Turkish, provided by drupal.org user Kartagis.
      array('tr', 'Abayı serdiler bize. Söyleyeceğim yüzlerine. Sanırım hepimiz aynı şeyi düşünüyoruz.', 'Abayi serdiler bize. Soyleyecegim yuzlerine. Sanirim hepimiz ayni seyi dusunuyoruz.'),
    );

    // Test each case both with a new instance of the transliteration class,
    // and with one that builds as it goes.
    $transliterator_service = $this->container->get('transliteration');

    foreach($cases as $case) {
      list($langcode, $original, $expected) = $case;
      $printable = (isset($case[3])) ? $case[3] : $original;
      $transliterator_class = new PhpTransliteration();
      $actual = $transliterator_class->transliterate($original, $langcode);
      $this->assertIdentical($actual, $expected, format_string('@original transliteration to @actual is identical to @expected for language @langcode in new class instance.', array(
        '@original' => $printable,
        '@langcode' => $langcode,
        '@expected' => $expected,
        '@actual' => $actual,
      )));

      $actual = $transliterator_service->transliterate($original, $langcode);
      $this->assertIdentical($actual, $expected, format_string('@original transliteration to @actual is identical to @expected for language @langcode in service instance.', array(
        '@original' => $printable,
        '@langcode' => $langcode,
        '@expected' => $expected,
        '@actual' => $actual,
      )));
    }

    // Test with max length, using German. It should never split up the
    // transliteration of a single character.
    $input = 'Ä Ö Ü Å Ø äöüåøhello';
    $trunc_output = 'Ae Oe Ue A O aeoe';
    $this->assertIdentical($trunc_output, $transliterator_service->transliterate($input, 'de', '?', 17), 'Truncating to 17 characters works');
    $this->assertIdentical($trunc_output, $transliterator_service->transliterate($input, 'de', '?', 18), 'Truncating to 18 characters works');

  }
}
