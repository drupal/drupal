<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Transliteration\TransliterationTest.
 */

namespace Drupal\system\Tests\Transliteration;

use Drupal\Component\Transliteration\PHPTransliteration;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the transliteration class.
 *
 * We need this to be a WebTestBase class because it uses drupal_container().
 */
class TransliterationTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('transliterate_test');

  public static function getInfo() {
    return array(
      'name' => 'Transliteration functionality',
      'description' => 'Tests the transliteration component',
      'group' => 'Transliteration',
    );
  }

  /**
   * Tests the PHPTransliteration class.
   */
  public function testPHPTransliteration() {
    $random = $this->randomName(10);
    // Make some strings with two, three, and four-byte characters for testing.
    // Note that the 3-byte character is overridden by the 'kg' language.
    $two_byte = 'Ä Ö Ü Å Ø äöüåøhello';
    // This is a Cyrrillic character that looks something like a u. See
    // http://www.unicode.org/charts/PDF/U0400.pdf
    $three_byte = html_entity_decode('&#x446;', ENT_NOQUOTES, 'UTF-8');
    // This is a Canadian Aboriginal character like a triangle. See
    // http://www.unicode.org/charts/PDF/U1400.pdf
    $four_byte = html_entity_decode('&#x1411;', ENT_NOQUOTES, 'UTF-8');
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
      // Test a language with no overrides.
      array('en', $two_byte, 'A O U A O aouaohello'),
      // Test language overrides provided by core.
      array('de', $two_byte, 'Ae Oe Ue A O aeoeueaohello'),
      array('de', $random, $random),
      array('dk', $two_byte, 'A O U Aa Oe aouaaoehello'),
      array('dk', $random, $random),
      array('kg', $three_byte, 'ts'),
      // Test the language override hook in the test module, which changes
      // the transliteration of Ä to Z.
      array('zz', $two_byte, 'Z O U A O aouaohello'),
      array('zz', $random, $random),
      // Test strings in some other languages.
      // Turkish, provided by drupal.org user Kartagis.
      array('tr', 'Abayı serdiler bize. Söyleyeceğim yüzlerine. Sanırım hepimiz aynı şeyi düşünüyoruz.', 'Abayi serdiler bize. Soyleyecegim yuzlerine. Sanirim hepimiz ayni seyi dusunuyoruz.'),
    );

    // Test each case both with a new instance of the transliteration class,
    // and with one that builds as it goes.
    $common_transliterator = drupal_container()->get('transliteration');

    foreach($cases as $case) {
      list($langcode, $before, $after) = $case;
      $transliterator = new PHPTransliteration();
      $actual = $transliterator->transliterate($before, $langcode);
      $this->assertEqual($after, $actual, format_string('@before is correctly transliterated to @after in new class (@actual) in language @langcode', array('@before' => $before, '@langcode' => $langcode, '@after' => $after, '@actual' => $actual)));

      $actual = $common_transliterator->transliterate($before, $langcode);
      $this->assertEqual($after, $actual, format_string('@before is correctly transliterated to @after in previously-used class (@actual) in language @langcode', array('@before' => $before, '@langcode' => $langcode, '@after' => $after, '@actual' => $actual)));
    }
  }
}
