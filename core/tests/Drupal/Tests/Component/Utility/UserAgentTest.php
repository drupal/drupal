<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\UserAgentTest.
 */

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
    return array(
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
    );
  }

  /**
   * Helper method to supply language mappings to testGetBestMatchingLangcode().
   *
   * @return array
   *   Language mappings.
   */
  protected function getMappings() {
    return array(
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
    );
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
    return array(
      // Equal qvalue for each language, choose the site preferred one.
      array('en,en-US,fr-CA,fr,es-MX', 'en'),
      array('en-US,en,fr-CA,fr,es-MX', 'en'),
      array('fr,en', 'en'),
      array('en,fr', 'en'),
      array('en-US,fr', 'en-US'),
      array('fr,en-US', 'en-US'),
      array('fr,fr-CA', 'fr-CA'),
      array('fr-CA,fr', 'fr-CA'),
      array('fr', 'fr-CA'),
      array('fr;q=1', 'fr-CA'),
      array('fr,es-MX', 'fr-CA'),
      array('fr,es', 'fr-CA'),
      array('es,fr', 'fr-CA'),
      array('es-MX,de', 'es-MX'),
      array('de,es-MX', 'es-MX'),

      // Different cases and whitespace.
      array('en', 'en'),
      array('En', 'en'),
      array('EN', 'en'),
      array(' en', 'en'),
      array('en ', 'en'),
      array('en, fr', 'en'),

      // A less specific language from the browser matches a more specific one
      // from the website, and the other way around for compatibility with
      // some versions of Internet Explorer.
      array('es', 'es-MX'),
      array('es-MX', 'es-MX'),
      array('pt', 'pt'),
      array('pt-PT', 'pt'),
      array('pt-PT;q=0.5,pt-BR;q=1,en;q=0.7', 'en'),
      array('pt-PT;q=1,pt-BR;q=0.5,en;q=0.7', 'en'),
      array('pt-PT;q=0.4,pt-BR;q=0.1,en;q=0.7', 'en'),
      array('pt-PT;q=0.1,pt-BR;q=0.4,en;q=0.7', 'en'),

      // Language code with several dashes are valid. The less specific language
      // from the browser matches the more specific one from the website.
      array('eh-oh-laa-laa', 'eh-oh-laa-laa'),
      array('eh-oh-laa', 'eh-oh-laa-laa'),
      array('eh-oh', 'eh-oh-laa-laa'),
      array('eh', 'eh-oh-laa-laa'),

      // Different qvalues.
      array('fr,en;q=0.5', 'fr-CA'),
      array('fr,en;q=0.5,fr-CA;q=0.25', 'fr'),

      // Silly wildcards are also valid.
      array('*,fr-CA;q=0.5', 'en'),
      array('*,en;q=0.25', 'fr-CA'),
      array('en,en-US;q=0.5,fr;q=0.25', 'en'),
      array('en-US,en;q=0.5,fr;q=0.25', 'en-US'),

      // Unresolvable cases.
      array('', FALSE),
      array('de,pl', FALSE),
      array('iecRswK4eh', FALSE),
      array($this->randomMachineName(10), FALSE),

      // Chinese langcodes.
      array('zh-cn, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hans'),
      array('zh-tw, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'),
      array('zh-hant, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'),
      array('zh-hans, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hans'),
      // @todo: This is copied from RFC4647 but our regex skips the numbers so
      // they where removed. Our code should be updated so private1-private2 is
      // valid. http://tools.ietf.org/html/rfc4647#section-3.4
      array('zh-hant-CN-x-private-private, en-us;q=0.90, en;q=0.80, zh;q=0.70', 'zh-hant'),
      array('zh-cn', 'zh-hans'),
      array('zh-sg', 'zh-hans'),
      array('zh-tw', 'zh-hant'),
      array('zh-hk', 'zh-hant'),
      array('zh-mo', 'zh-hant'),
      array('zh-hans', 'zh-hans'),
      array('zh-hant', 'zh-hant'),
      array('zh-chs', 'zh-hans'),
      array('zh-cht', 'zh-hant'),
    );
  }

}
