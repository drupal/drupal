<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageBrowserDetectionUnitTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * Test browser language detection.
 */
class LanguageBrowserDetectionUnitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Browser language detection',
      'description' => 'Tests for the browser language detection.',
      'group' => 'Language',
    );
  }

  /**
   * Unit tests for the language_from_browser() function.
   */
  function testLanguageFromBrowser() {
    // Load the required functions.
    require_once DRUPAL_ROOT . '/core/modules/language/language.negotiation.inc';

    $languages = array(
      // In our test case, 'en' has priority over 'en-US'.
      'en' => (object) array(
        'langcode' => 'en',
      ),
      'en-US' => (object) array(
        'langcode' => 'en-US',
      ),
      // But 'fr-CA' has priority over 'fr'.
      'fr-CA' => (object) array(
        'langcode' => 'fr-CA',
      ),
      'fr' => (object) array(
        'langcode' => 'fr',
      ),
      // 'es-MX' is alone.
      'es-MX' => (object) array(
        'langcode' => 'es-MX',
      ),
      // 'pt' is alone.
      'pt' => (object) array(
        'langcode' => 'pt',
      ),
      // Language codes with more then one dash are actually valid.
      // eh-oh-laa-laa is the official language code of the Teletubbies.
      'eh-oh-laa-laa' => (object) array(
        'langcode' => 'eh-oh-laa-laa',
      ),
    );

    $test_cases = array(
      // Equal qvalue for each language, choose the site prefered one.
      'en,en-US,fr-CA,fr,es-MX' => 'en',
      'en-US,en,fr-CA,fr,es-MX' => 'en',
      'fr,en' => 'en',
      'en,fr' => 'en',
      'en-US,fr' => 'en',
      'fr,en-US' => 'en',
      'fr,fr-CA' => 'fr-CA',
      'fr-CA,fr' => 'fr-CA',
      'fr' => 'fr-CA',
      'fr;q=1' => 'fr-CA',
      'fr,es-MX' => 'fr-CA',
      'fr,es' => 'fr-CA',
      'es,fr' => 'fr-CA',
      'es-MX,de' => 'es-MX',
      'de,es-MX' => 'es-MX',

      // Different cases and whitespace.
      'en' => 'en',
      'En' => 'en',
      'EN' => 'en',
      ' en' => 'en',
      'en ' => 'en',
      'en, fr' => 'en',

      // A less specific language from the browser matches a more specific one
      // from the website, and the other way around for compatibility with
      // some versions of Internet Explorer.
      'es' => 'es-MX',
      'es-MX' => 'es-MX',
      'pt' => 'pt',
      'pt-PT' => 'pt',
      'pt-PT;q=0.5,pt-BR;q=1,en;q=0.7' => 'en',
      'pt-PT;q=1,pt-BR;q=0.5,en;q=0.7' => 'en',
      'pt-PT;q=0.4,pt-BR;q=0.1,en;q=0.7' => 'en',
      'pt-PT;q=0.1,pt-BR;q=0.4,en;q=0.7' => 'en',

      // Language code with several dashes are valid. The less specific language
      // from the browser matches the more specific one from the website.
      'eh-oh-laa-laa' => 'eh-oh-laa-laa',
      'eh-oh-laa' => 'eh-oh-laa-laa',
      'eh-oh' => 'eh-oh-laa-laa',
      'eh' => 'eh-oh-laa-laa',

      // Different qvalues.
      'en-US,en;q=0.5,fr;q=0.25' => 'en-US',
      'fr,en;q=0.5' => 'fr-CA',
      'fr,en;q=0.5,fr-CA;q=0.25' => 'fr',

      // Silly wildcards are also valid.
      '*,fr-CA;q=0.5' => 'en',
      '*,en;q=0.25' => 'fr-CA',
      'en,en-US;q=0.5,fr;q=0.25' => 'en',
      'en-US,en;q=0.5,fr;q=0.25' => 'en-US',

      // Unresolvable cases.
      '' => FALSE,
      'de,pl' => FALSE,
      'iecRswK4eh' => FALSE,
      $this->randomName(10) => FALSE,
    );

    foreach ($test_cases as $accept_language => $expected_result) {
      $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept_language;
      $result = language_from_browser($languages);
      $this->assertIdentical($result, $expected_result, t("Language selection '@accept-language' selects '@result', result = '@actual'", array('@accept-language' => $accept_language, '@result' => $expected_result, '@actual' => isset($result) ? $result : 'none')));
    }
  }
}
