<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\UserAgent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests bytes size parsing helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\UserAgent
 */
class UserAgentTest extends UnitTestCase {

  /**
   * Helper method to supply language codes to testGetBestMatchingLangcode().
   *
   * @return array
   *   Language codes, ordered by priority.
   */
  protected function getLanguages() {
    return [
      // In our test case, 'en' has priority over 'en-US'.
      'en',
      'en-US',
      // But 'fr-CA' has priority over 'fr'.
      'fr-CA',
      'fr',
      // 'es-MX' is alone.
      'es-MX',
      // 'pt' is alone.
      'pt',
      // Language codes with more then one dash are actually valid.
      // eh-oh-laa-laa is the official language code of the Teletubbies.
      'eh-oh-laa-laa',
      // Chinese languages.
      'zh-hans',
      'zh-hant',
      'zh-hant-tw',
    ];
  }

  /**
   * Helper method to supply language mappings to testGetBestMatchingLangcode().
   *
   * @return array
   *   Language mappings.
   */
  protected function getMappings() {
    return [
      'no' => 'nb',
      'pt' => 'pt-pt',
      'zh' => 'zh-hans',
      'zh-tw' => 'zh-hant',
      'zh-hk' => 'zh-hant',
      'zh-mo' => 'zh-hant',
      'zh-cht' => 'zh-hant',
      'zh-cn' => 'zh-hans',
      'zh-sg' => 'zh-hans',
      'zh-chs' => 'zh-hans',
    ];
  }

  /**
   * Test matching language from user agent.
   *
   * @dataProvider providerTestGetBestMatchingLangcode
   * @covers ::getBestMatchingLangcode
   */
  public function testGetBestMatchingLangcode($accept_language, $expected) {
    $result = UserAgent::getBestMatchingLangcode($accept_language, $this->getLanguages(), $this->getMappings());
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testGetBestMatchingLangcode().
   *
   * @return array
   *   - An accept-language string.
   *   - Expected best matching language code.
   */
  public function providerTestGetBestMatchingLangcode() {
    return [
      // Equal qvalue for each language, choose the site preferred one.
      ['en,en-US,fr-CA,fr,es-MX', 'en'],
      ['en-US,en,fr-CA,fr,es-MX', 'en'],
      ['fr,en', 'en'],
      ['en,fr', 'en'],
      ['en-US,fr', 'en-US'],
      ['fr,en-US', 'en-US'],
      ['fr,fr-CA', 'fr-CA'],
      ['fr-CA,fr', 'fr-CA'],
      ['fr', 'fr-CA'],
      ['fr;q=1', 'fr-CA'],
      ['fr,es-MX', 'fr-CA'],
      ['fr,es', 'fr-CA'],
      ['es,fr', 'fr-CA'],
      ['es-MX,de', 'es-MX'],
      ['de,es-MX', 'es-MX'],

      // Different cases and whitespace.
      ['en', 'en'],
      ['En', 'en'],
      ['EN', 'en'],
      [' en', 'en'],
      ['en ', 'en'],
      ['en, fr', 'en'],

      // A less specific language from the browser matches a more specific one
      // from the website, and the other way around for compatibility with
      // some versions of Internet Explorer.
      ['es', 'es-MX'],
      ['es-MX', 'es-MX'],
      ['pt', 'pt'],
      ['pt-PT', 'pt'],
      ['pt-PT;q=0.5,pt-BR;q=1,en;q=0.7', 'en'],
      ['pt-PT;q=1,pt-BR;q=0.5,en;q=0.7', 'en'],
      ['pt-PT;q=0.4,pt-BR;q=0.1,en;q=0.7', 'en'],
      ['pt-PT;q=0.1,pt-BR;q=0.4,en;q=0.7', 'en'],

      // Language code with several dashes are valid. The less specific language
      // from the browser matches the more specific one from the website.
      ['eh-oh-laa-laa', 'eh-oh-laa-laa'],
      ['eh-oh-laa', 'eh-oh-laa-laa'],
      ['eh-oh', 'eh-oh-laa-laa'],
      ['eh', 'eh-oh-laa-laa'],

      // Different qvalues.
      ['fr,en;q=0.5', 'fr-CA'],
      ['fr,en;q=0.5,fr-CA;q=0.25', 'fr'],

      // Silly wildcards are also valid.
      ['*,fr-CA;q=0.5', 'en'],
      ['*,en;q=0.25', 'fr-CA'],
      ['en,en-US;q=0.5,fr;q=0.25', 'en'],
      ['en-US,en;q=0.5,fr;q=0.25', 'en-US'],

      // Unresolvable cases.
      ['', FALSE],
      ['de,pl', FALSE],
      ['iecRswK4eh', FALSE],
      [$this->randomMachineName(10), FALSE],

      // Chinese langcodes.
      ['zh-cn, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hans'],
      ['zh-tw, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'],
      ['zh-hant, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'],
      ['zh-hans, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hans'],
      // @todo: This is copied from RFC4647 but our regex skips the numbers so
      // they where removed. Our code should be updated so private1-private2 is
      // valid. http://tools.ietf.org/html/rfc4647#section-3.4
      ['zh-hant-CN-x-private-private, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'],
      ['zh-cn', 'zh-hans'],
      ['zh-sg', 'zh-hans'],
      ['zh-tw', 'zh-hant'],
      ['zh-hk', 'zh-hant'],
      ['zh-mo', 'zh-hant'],
      ['zh-hans', 'zh-hans'],
      ['zh-hant', 'zh-hant'],
      ['zh-chs', 'zh-hans'],
      ['zh-cht', 'zh-hant'],
    ];
  }

}
