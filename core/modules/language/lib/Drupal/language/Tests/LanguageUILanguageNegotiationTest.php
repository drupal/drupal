<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageUILanguageNegotiationTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test UI language negotiation
 *
 * 1. URL (PATH) > DEFAULT
 *    UI Language base on URL prefix, browser language preference has no
 *    influence:
 *      admin/config
 *        UI in site default language
 *      zh-hans/admin/config
 *        UI in Chinese
 *      blah-blah/admin/config
 *        404
 * 2. URL (PATH) > BROWSER > DEFAULT
 *        admin/config
 *          UI in user's browser language preference if the site has that
 *          language enabled, if not, the default language
 *        zh-hans/admin/config
 *          UI in Chinese
 *        blah-blah/admin/config
 *          404
 * 3. URL (DOMAIN) > DEFAULT
 *        http://example.com/admin/config
 *          UI language in site default
 *        http://example.cn/admin/config
 *          UI language in Chinese
 */
class LanguageUILanguageNegotiationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'UI language negotiation',
      'description' => 'Test UI language switching by url path prefix and domain.',
      'group' => 'Language',
    );
  }

  function setUp() {
    // We marginally use interface translation functionality here, so need to
    // use the locale module instead of language only, but the 90% of the test
    // is about the negotiation process which is solely in language module.
    parent::setUp(array('locale', 'language_test', 'block'));
    require_once DRUPAL_ROOT . '/core/includes/language.inc';
    $admin_user = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages', 'administer blocks'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests for language switching by URL path.
   */
  function testUILanguageNegotiation() {
    // A few languages to switch to.
    // This one is unknown, should get the default lang version.
    $langcode_unknown = 'blah-blah';
    // For testing browser lang preference.
    $langcode_browser_fallback = 'vi';
    // For testing path prefix.
    $langcode = 'zh-hans';
    // For setting browser language preference to 'vi'.
    $http_header_browser_fallback = array("Accept-Language: $langcode_browser_fallback;q=1");
    // For setting browser language preference to some unknown.
    $http_header_blah = array("Accept-Language: blah;q=1");

    // This domain should switch the UI to Chinese.
    $language_domain = 'example.cn';

    // Setup the site languages by installing two languages.
    $language = (object) array(
      'langcode' => $langcode_browser_fallback,
    );
    language_save($language);
    $language = (object) array(
      'langcode' => $langcode,
    );
    language_save($language);

    // We will look for this string in the admin/config screen to see if the
    // corresponding translated string is shown.
    $default_string = 'Configure languages for content and the user interface';

    // Set the default language in order for the translated string to be registered
    // into database when seen by t(). Without doing this, our target string
    // is for some reason not found when doing translate search. This might
    // be some bug.
    drupal_static_reset('language_list');
    $languages = language_list();
    variable_set('language_default', (array) $languages['vi']);
    // First visit this page to make sure our target string is searchable.
    $this->drupalGet('admin/config');
    // Now the t()'ed string is in db so switch the language back to default.
    variable_del('language_default');

    // Translate the string.
    $language_browser_fallback_string = "In $langcode_browser_fallback In $langcode_browser_fallback In $langcode_browser_fallback";
    $language_string = "In $langcode In $langcode In $langcode";
    // Do a translate search of our target string.
    $edit = array( 'string' => $default_string);
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Filter'));
    // Should find the string and now click edit to post translated string.
    $this->clickLink('edit');
    $edit = array(
      "translations[$langcode_browser_fallback][0]" => $language_browser_fallback_string,
      "translations[$langcode][0]" => $language_string,
    );
    $this->drupalPost(NULL, $edit, t('Save translations'));

    // Configure URL language rewrite.
    variable_set('language_negotiation_url_type', LANGUAGE_TYPE_INTERFACE);

    $tests = array(
      // Default, browser preference should have no influence.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_DEFAULT),
        'path' => 'admin/config',
        'expect' => $default_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_DEFAULT,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > DEFAULT: no language prefix, UI language is default and the browser language preference setting is not used.',
      ),
      // Language prefix.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_DEFAULT),
        'path' => "$langcode/admin/config",
        'expect' => $language_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_URL,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > DEFAULT: with language prefix, UI language is switched based on path prefix',
      ),
      // Default, go by browser preference.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_BROWSER),
        'path' => 'admin/config',
        'expect' => $language_browser_fallback_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_BROWSER,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > BROWSER: no language prefix, UI language is determined by browser language preference',
      ),
      // Prefix, switch to the language.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_BROWSER),
        'path' => "$langcode/admin/config",
        'expect' => $language_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_URL,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > BROWSER: with langage prefix, UI language is based on path prefix',
      ),
      // Default, browser language preference is not one of site's lang.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_BROWSER, LANGUAGE_NEGOTIATION_DEFAULT),
        'path' => 'admin/config',
        'expect' => $default_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_DEFAULT,
        'http_header' => $http_header_blah,
        'message' => 'URL (PATH) > BROWSER > DEFAULT: no language prefix and browser language preference set to unknown language should use default language',
      ),
    );

    foreach ($tests as $test) {
      $this->runTest($test);
    }

    // Unknown language prefix should return 404.
    variable_set('language_negotiation_' . LANGUAGE_TYPE_INTERFACE, language_language_negotiation_info());
    $this->drupalGet("$langcode_unknown/admin/config", array(), $http_header_browser_fallback);
    $this->assertResponse(404, "Unknown language path prefix should return 404");

    // Setup for domain negotiation, first configure the language to have domain
    // URL.
    $edit = array("domain[$langcode]" => $language_domain);
    $this->drupalPost("admin/config/regional/language/detection/url", $edit, t('Save configuration'));
    // Set the site to use domain language negotiation.

    $tests = array(
      // Default domain, browser preference should have no influence.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_DEFAULT),
        'language_negotiation_url_part' => LANGUAGE_NEGOTIATION_URL_DOMAIN,
        'path' => 'admin/config',
        'expect' => $default_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_DEFAULT,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (DOMAIN) > DEFAULT: default domain should get default language',
      ),
      // Language domain specific URL, we set the $_SERVER['HTTP_HOST'] in
      // language_test.module hook_boot() to simulate this.
      array(
        'language_negotiation' => array(LANGUAGE_NEGOTIATION_URL, LANGUAGE_NEGOTIATION_DEFAULT),
        'language_negotiation_url_part' => LANGUAGE_NEGOTIATION_URL_DOMAIN,
        'language_test_domain' => $language_domain . ':88',
        'path' => 'admin/config',
        'expect' => $language_string,
        'expected_method_id' => LANGUAGE_NEGOTIATION_URL,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (DOMAIN) > DEFAULT: domain example.cn should switch to Chinese',
      ),
    );

    foreach ($tests as $test) {
      $this->runTest($test);
    }
  }

  protected function runTest($test) {
    if (!empty($test['language_negotiation'])) {
      $method_weights = array_flip($test['language_negotiation']);
      language_negotiation_set(LANGUAGE_TYPE_INTERFACE, $method_weights);
    }
    if (!empty($test['language_negotiation_url_part'])) {
      variable_set('language_negotiation_url_part', $test['language_negotiation_url_part']);
    }
    if (!empty($test['language_test_domain'])) {
      variable_set('language_test_domain', $test['language_test_domain']);
    }
    $this->drupalGet($test['path'], array(), $test['http_header']);
    $this->assertText($test['expect'], $test['message']);
    $this->assertText(t('Language negotiation method: @name', array('@name' => $test['expected_method_id'])));
  }

  /**
   * Test URL language detection when the requested URL has no language.
   */
  function testUrlLanguageFallback() {
    // Add the Italian language.
    $langcode_browser_fallback = 'it';
    $language = (object) array(
      'langcode' => $langcode_browser_fallback,
    );
    language_save($language);
    $languages = language_list();

    // Enable the path prefix for the default language: this way any unprefixed
    // URL must have a valid fallback value.
    $edit = array('prefix[en]' => 'en');
    $this->drupalPost('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Enable browser and URL language detection.
    $edit = array(
      'language_interface[enabled][language-browser]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-browser]' => -8,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->drupalGet('admin/config/regional/language/detection');

    // Enable the language switcher block.
    $edit = array('blocks[language_language_interface][region]' => 'sidebar_first');
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Access the front page without specifying any valid URL language prefix
    // and having as browser language preference a non-default language.
    $http_header = array("Accept-Language: $langcode_browser_fallback;q=1");
    $language = (object) array('langcode' => '');
    $this->drupalGet('', array('language' => $language), $http_header);

    // Check that the language switcher active link matches the given browser
    // language.
    $args = array(':url' => base_path() . $GLOBALS['script_path'] . $langcode_browser_fallback);
    $fields = $this->xpath('//div[@id="block-language-language-interface"]//a[@class="language-link active" and starts-with(@href, :url)]', $args);
    $this->assertTrue($fields[0] == $languages[$langcode_browser_fallback]->name, t('The browser language is the URL active language'));

    // Check that URLs are rewritten using the given browser language.
    $fields = $this->xpath('//p[@id="site-name"]/strong/a[@rel="home" and @href=:url]', $args);
    $this->assertTrue($fields[0] == 'Drupal', t('URLs are rewritten using the browser language.'));
  }

  /**
   * Tests url() when separate domains are used for multiple languages.
   */
  function testLanguageDomain() {
    // Add the Italian language.
    $langcode = 'it';
    $language = (object) array(
      'langcode' => $langcode,
    );
    language_save($language);
    $languages = language_list();

    // Enable browser and URL language detection.
    $edit = array(
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Change the domain for the Italian language.
    $edit = array(
      'language_negotiation_url_part' => 1,
      'domain[it]' => 'it.example.com',
    );
    $this->drupalPost('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Build the link we're going to test.
    $link = 'it.example.com/admin';

    global $is_https;
    // Test URL in another language: http://it.example.com/admin.
    // Base path gives problems on the testbot, so $correct_link is hard-coded.
    // @see UrlAlterFunctionalTest::assertUrlOutboundAlter (path.test).
    $italian_url = url('admin', array('language' => $languages['it'], 'script' => ''));
    $url_scheme = ($is_https) ? 'https://' : 'http://';
    $correct_link = $url_scheme . $link;
    $this->assertTrue($italian_url == $correct_link, t('The url() function returns the right url (@url) in accordance with the chosen language', array('@url' => $italian_url)));

    // Test https via options.
    variable_set('https', TRUE);
    $italian_url = url('admin', array('https' => TRUE, 'language' => $languages['it'], 'script' => ''));
    $correct_link = 'https://' . $link;
    $this->assertTrue($italian_url == $correct_link, t('The url() function returns the right https url (via options) (@url) in accordance with the chosen language', array('@url' => $italian_url)));
    variable_set('https', FALSE);

    // Test https via current url scheme.
    $temp_https = $is_https;
    $is_https = TRUE;
    $italian_url = url('admin', array('language' => $languages['it'], 'script' => ''));
    $correct_link = 'https://' . $link;
    $this->assertTrue($italian_url == $correct_link, t('The url() function returns the right url (via current url scheme) (@url) in accordance with the chosen language', array('@url' => $italian_url)));
    $is_https = $temp_https;
  }
}
