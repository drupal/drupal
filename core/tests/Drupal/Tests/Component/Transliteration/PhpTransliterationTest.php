<?php

namespace Drupal\Tests\Component\Transliteration;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Component\Utility\Random;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * Tests Transliteration component functionality.
 *
 * @group Transliteration
 *
 * @coversDefaultClass \Drupal\Component\Transliteration\PhpTransliteration
 */
class PhpTransliterationTest extends TestCase {

  /**
   * Tests the PhpTransliteration::removeDiacritics() function.
   *
   * @param string $original
   *   The language code to test.
   * @param string $expected
   *   The expected return from PhpTransliteration::removeDiacritics().
   *
   * @dataProvider providerTestPhpTransliterationRemoveDiacritics
   */
  public function testRemoveDiacritics($original, $expected) {
    $transliterator_class = new PhpTransliteration();
    $result = $transliterator_class->removeDiacritics($original);
    $this->assertEquals($expected, $result);
  }

  /**
   * Provides data for self::testRemoveDiacritics().
   *
   * @return array
   *   An array of arrays, each containing the parameters for
   *   self::testRemoveDiacritics().
   */
  public function providerTestPhpTransliterationRemoveDiacritics() {
    // cSpell:disable
    return [
      // Test all characters in the Unicode range 0x00bf to 0x017f.
      ['ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏ', 'AAAAAAÆCEEEEIIII'],
      ['ÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß', 'ÐNOOOOO×OUUUUYÞß'],
      ['àáâãäåæçèéêëìíîï', 'aaaaaaæceeeeiiii'],
      ['ðñòóôõö÷øùúûüýþÿ', 'ðnooooo÷ouuuuyþy'],
      ['ĀāĂăĄąĆćĈĉĊċČčĎď', 'AaAaAaCcCcCcCcDd'],
      ['ĐđĒēĔĕĖėĘęĚěĜĝĞğ', 'DdEeEeEeEeEeGgGg'],
      ['ĠġĢģĤĥĦħĨĩĪīĬĭĮį', 'GgGgHhHhIiIiIiIi'],
      ['İıĲĳĴĵĶķĸĹĺĻļĽľĿ', 'IiĲĳJjKkĸLlLlLlL'],
      ['ŀŁłŃńŅņŇňŉŊŋŌōŎŏ', 'lLlNnNnNnŉŊŋOoOo'],
      ['ŐőŒœŔŕŖŗŘřŚśŜŝŞş', 'OoŒœRrRrRrSsSsSs'],
      ['ŠšŢţŤťŦŧŨũŪūŬŭŮů', 'SsTtTtTtUuUuUuUu'],
      ['ŰűŲųŴŵŶŷŸŹźŻżŽž', 'UuUuWwYyYZzZzZz'],

      // Test all characters in the Unicode range 0x01CD to 0x024F.
      ['ǍǎǏ', 'AaI'],
      ['ǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟ', 'iOoUuUuUuUuUuǝAa'],
      ['ǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯ', 'AaÆæGgGgKkOoOoƷʒ'],
      ['ǰǱǲǳǴǵǶǷǸǹǺǻǼǽǾǿ', 'jǱǲǳGgǶǷNnAaÆæOo'],
      ['ȀȁȂȃȄȅȆȇȈȉȊȋȌȍȎȏ', 'AaAaEeEeIiIiOoOo'],
      ['ȐȑȒȓȔȕȖȗȘșȚțȜȝȞȟ', 'RrRrUuUuSsTtȜȝHh'],
      ['ȠȡȢȣȤȥȦȧȨȩȪȫȬȭȮȯ', 'ȠȡȢȣZzAaEeOoOoOo'],
      ['ȰȱȲȳȴȵȶȷȸȹȺȻȼȽȾȿ', 'OoYylntjȸȹACcLTs'],
      ['ɀɁɂɃɄɅɆɇɈɉɊɋɌɍɎɏ', 'zɁɂBUɅEeJjQqRrYy'],
    ];
    // cSpell:enable
  }

  /**
   * Tests the PhpTransliteration class.
   *
   * @param string $langcode
   *   The language code to test.
   * @param string $original
   *   The original string.
   * @param string $expected
   *   The expected return from PhpTransliteration::transliterate().
   * @param string $unknown_character
   *   (optional) The character to substitute for characters in $string without
   *   transliterated equivalents. Defaults to '?'.
   * @param int $max_length
   *   (optional) If provided, return at most this many characters, ensuring
   *   that the transliteration does not split in the middle of an input
   *   character's transliteration.
   *
   * @dataProvider providerTestPhpTransliteration
   */
  public function testPhpTransliteration($langcode, $original, $expected, $unknown_character = '?', $max_length = NULL) {
    $transliterator_class = new PhpTransliteration();
    $actual = $transliterator_class->transliterate($original, $langcode, $unknown_character, $max_length);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for self::testPhpTransliteration().
   *
   * @return array
   *   An array of arrays, each containing the parameters for
   *   self::testPhpTransliteration().
   */
  public function providerTestPhpTransliteration() {
    $random_generator = new Random();
    $random = $random_generator->string(10);
    // Make some strings with two, three, and four-byte characters for testing.
    // Note that the 3-byte character is overridden by the 'kg' language.
    // cSpell:disable-next-line
    $two_byte = 'Ä Ö Ü Å Ø äöüåøhello';
    // This is a Cyrillic character that looks something like a "u". See
    // http://www.unicode.org/charts/PDF/U0400.pdf
    $three_byte = html_entity_decode('&#x446;', ENT_NOQUOTES, 'UTF-8');
    // This is a Canadian Aboriginal character like a triangle. See
    // http://www.unicode.org/charts/PDF/U1400.pdf
    $four_byte = html_entity_decode('&#x1411;', ENT_NOQUOTES, 'UTF-8');
    // These are two Gothic alphabet letters. See
    // http://wikipedia.org/wiki/Gothic_alphabet
    // They are not in our tables, but should at least give us '?' (unknown).
    $five_byte = html_entity_decode('&#x10330;&#x10338;', ENT_NOQUOTES, 'UTF-8');

    // cSpell:disable
    return [
      // Each test case is language code, input, output, unknown character, max
      // length.
      // Test ASCII in English.
      ['en', $random, $random],
      // Test ASCII in some other language with no overrides.
      ['fr', $random, $random],
      // Test 3 and 4-byte characters in a language without overrides.
      // Note: if the data tables change, these will need to change too! They
      // are set up to test that data table loading works, so values come
      // directly from the data files.
      ['fr', $three_byte, 'c'],
      ['fr', $four_byte, 'wii'],
      // Test 5-byte characters.
      ['en', $five_byte, '??'],
      // Test a language with no overrides.
      ['en', $two_byte, 'A O U A O aouaohello'],
      // Test language overrides provided by core.
      ['de', $two_byte, 'Ae Oe Ue A O aeoeueaohello'],
      ['de', $random, $random],
      ['da', $two_byte, 'A O U Aa Oe aouaaoehello'],
      ['da', $random, $random],
      ['kg', $three_byte, 'ts'],
      // Test strings in some other languages.
      // Ukrainian pangram.
      ['uk', 'На подушечці форми любої є й ґудзик щоб пірʼя геть жовте сховати.', 'Na podushechtsi formy lyuboyi ye y gudzyk shchob pirya het zhovte skhovaty.'],
      // Turkish, provided by drupal.org user Kartagis.
      ['tr', 'Abayı serdiler bize. Söyleyeceğim yüzlerine. Sanırım hepimiz aynı şeyi düşünüyoruz.', 'Abayi serdiler bize. Soyleyecegim yuzlerine. Sanirim hepimiz ayni seyi dusunuyoruz.'],
      // Max length.
      ['de', $two_byte, 'Ae Oe Ue A O aeoe', '?', 17],
      // Do not split up the transliteration of a single character.
      ['de', $two_byte, 'Ae Oe Ue A O aeoe', '?', 18],
      // Illegal/unknown unicode.
      ['en', chr(0xF8) . chr(0x80) . chr(0x80) . chr(0x80) . chr(0x80), '?????'],
      ['en', chr(0xF8) . chr(0x80) . chr(0x80) . chr(0x80) . chr(0x80), '-----', '-'],
      ['en', 'Hel' . chr(0x80) . 'o World', 'Hel?o World'],
      ['en', 'Hell' . chr(0x80) . ' World', 'Hell? World'],
      // Non default replacement.
      ['en', chr(0x80) . 'ello World', '_ello World', '_'],
      // Keep the original question marks.
      ['en', chr(0xF8) . '?' . chr(0x80), '???'],
      ['en', chr(0x80) . 'ello ? World?', '_ello ? World?', '_'],
      ['pl', 'aąeę' . chr(0x80) . 'oółżźz ?', 'aaee?oolzzz ?'],
      // Non-US-ASCII replacement.
      ['en', chr(0x80) . 'ello World?', 'Oello World?', 'Ö'],
      ['pl', chr(0x80) . 'óóść', 'ooosc', 'ó'],
      // Ensure question marks are replaced when max length used.
      ['en', chr(0x80) . 'ello ? World?', '_ello ?', '_', 7],
      // Empty replacement.
      ['en', chr(0x80) . 'ello World' . chr(0xF8), 'ello World', ''],
      // Not affecting spacing from the beginning and end of a string.
      ['en', ' Hello Abventor! ', ' Hello Abventor! '],
      ['pl', ' Drupal Kraków Community', ' Drupal Krakow ', '?', 15],
      // Keep many spaces between words.
      ['en', 'Too    many    spaces between words !', 'Too    many    spaces between words !'],
    ];
    // cSpell:enable
  }

  /**
   * Tests inclusion is safe.
   *
   * @covers ::readLanguageOverrides
   */
  public function testSafeInclude() {
    // The overrides in the transliteration data directory transliterates 0x82
    // into "safe" but the overrides one directory higher transliterates the
    // same character into "security hole". So by using "../index" as the
    // language code we can test the ../ is stripped from the langcode.
    vfsStream::setup('transliteration', NULL, [
      'index.php' => '<?php $overrides = ["../index" => [0x82 => "security hole"]];',
      'dir' => [
        'index.php' => '<?php $overrides = ["../index" => [0x82 => "safe"]];',
      ],
    ]);
    $transliteration = new PhpTransliteration(vfsStream::url('transliteration/dir'));
    $transliterated = $transliteration->transliterate(chr(0xC2) . chr(0x82), '../index');
    $this->assertSame('safe', $transliterated);
  }

}
