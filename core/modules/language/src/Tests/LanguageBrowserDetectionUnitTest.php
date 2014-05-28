<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageBrowserDetectionUnitTest.
 */

namespace Drupal\language\Tests;

use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test browser language detection.
 */
class LanguageBrowserDetectionUnitTest extends WebTestBase {

  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Browser language detection',
      'description' => 'Tests for the browser language detection.',
      'group' => 'Language',
    );
  }

  /**
   * Unit tests for the language_from_browser() function.
   *
   * @see language_from_browser().
   */
  function testLanguageFromBrowser() {
    // The order of the languages is only important if the browser language
    // codes are having the same qvalue, otherwise the one with the highest
    // qvalue is preferred. The automatically generated generic tags are always
    // having a lower qvalue.

    $languages = array(
      // In our test case, 'en' has priority over 'en-US'.
      'en' => new Language(array(
        'id' => 'en',
      )),
      'en-US' => new Language(array(
        'id' => 'en-US',
      )),
      // But 'fr-CA' has priority over 'fr'.
      'fr-CA' => new Language(array(
        'id' => 'fr-CA',
      )),
      'fr' => new Language(array(
        'id' => 'fr',
      )),
      // 'es-MX' is alone.
      'es-MX' => new Language(array(
        'id' => 'es-MX',
      )),
      // 'pt' is alone.
      'pt' => new Language(array(
        'id' => 'pt',
      )),
      // Language codes with more then one dash are actually valid.
      // eh-oh-laa-laa is the official language code of the Teletubbies.
      'eh-oh-laa-laa' => new Language(array(
        'id' => 'eh-oh-laa-laa',
      )),
      // Chinese languages.
      'zh-hans' => new Language(array(
        'id' => 'zh-hans',
      )),
      'zh-hant' => new Language(array(
        'id' => 'zh-hant',
      )),
      'zh-hant-tw' => new Language(array(
        'id' => 'zh-hant',
      )),
    );

    $test_cases = array(
      // Equal qvalue for each language, choose the site preferred one.
      'en,en-US,fr-CA,fr,es-MX' => 'en',
      'en-US,en,fr-CA,fr,es-MX' => 'en',
      'fr,en' => 'en',
      'en,fr' => 'en',
      'en-US,fr' => 'en-US',
      'fr,en-US' => 'en-US',
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

      // Chinese langcodes.
      'zh-cn, en-us;q=0.90, en;q=0.80, zh;q=0.70' => 'zh-hans',
      'zh-tw, en-us;q=0.90, en;q=0.80, zh;q=0.70' => 'zh-hant',
      'zh-hant, en-us;q=0.90, en;q=0.80, zh;q=0.70' => 'zh-hant',
      'zh-hans, en-us;q=0.90, en;q=0.80, zh;q=0.70' => 'zh-hans',
      'zh-cn' => 'zh-hans',
      'zh-sg' => 'zh-hans',
      'zh-tw' => 'zh-hant',
      'zh-hk' => 'zh-hant',
      'zh-mo' => 'zh-hant',
      'zh-hans' => 'zh-hans',
      'zh-hant' => 'zh-hant',
      'zh-chs' => 'zh-hans',
      'zh-cht' => 'zh-hant',
    );

    $mappings = $this->container->get('config.factory')->get('language.mappings')->get();
    foreach ($test_cases as $accept_language => $expected_result) {
      $result = UserAgent::getBestMatchingLangcode($accept_language, array_keys($languages), $mappings);
      $this->assertIdentical($result, $expected_result, format_string("Language selection '@accept-language' selects '@result', result = '@actual'", array('@accept-language' => $accept_language, '@result' => $expected_result, '@actual' => isset($result) ? $result : 'none')));
    }
  }

  /**
   * Tests for adding, editing and deleting mappings between browser language
   * codes and Drupal language codes.
   */
  function testUIBrowserLanguageMappings() {
    // User to manage languages.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Check that the configure link exists.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertLinkByHref('admin/config/regional/language/detection/browser');

    // Check that defaults are loaded from language.mappings.yml.
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->assertField('edit-mappings-zh-cn-browser-langcode', 'zh-cn', 'Chinese browser language code found.');
    $this->assertField('edit-mappings-zh-cn-drupal-langcode', 'zh-hans-cn', 'Chinese Drupal language code found.');

    // Delete zh-cn language code.
    $browser_langcode = 'zh-cn';
    $this->drupalGet('admin/config/regional/language/detection/browser/delete/' . $browser_langcode);
    $message = t('Are you sure you want to delete @browser_langcode?', array(
      '@browser_langcode' => $browser_langcode,
    ));
    $this->assertRaw($message);

    // Confirm the delete.
    $edit = array();
    $this->drupalPostForm('admin/config/regional/language/detection/browser/delete/' . $browser_langcode, $edit, t('Confirm'));

    // Check that ch-zn no longer exists.
    $this->assertNoField('edit-mappings-zh-cn-browser-langcode', 'Chinese browser language code no longer exists.');

    // Add a new custom mapping.
    $edit = array(
      'new_mapping[browser_langcode]' => 'xx',
      'new_mapping[drupal_langcode]' => 'en',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->assertField('edit-mappings-xx-browser-langcode', 'xx', 'Browser language code found.');
    $this->assertField('edit-mappings-xx-drupal-langcode', 'en', 'Drupal language code found.');

    // Add the same custom mapping again.
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertText('Browser language codes must be unique.');

    // Change browser language code of our custom mapping to zh-sg.
    $edit = array(
      'mappings[xx][browser_langcode]' => 'zh-sg',
      'mappings[xx][drupal_langcode]' => 'en',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertText(t('Browser language codes must be unique.'));

    // Change Drupal language code of our custom mapping to zh-hans.
    $edit = array(
      'mappings[xx][browser_langcode]' => 'xx',
      'mappings[xx][drupal_langcode]' => 'zh-hans',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->assertField('edit-mappings-xx-browser-langcode', 'xx', 'Browser language code found.');
    $this->assertField('edit-mappings-xx-drupal-langcode', 'zh-hans', 'Drupal language code found.');
  }
}
