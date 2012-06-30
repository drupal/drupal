<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageUrlRewritingTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test that URL rewriting works as expected.
 */
class LanguageUrlRewritingTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'URL rewriting',
      'description' => 'Test that URL rewriting works as expected.',
      'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp('language');

    // Create and login user.
    $this->web_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($this->web_user);

    // Install French language.
    $edit = array();
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => 1);
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Reset static caching.
    drupal_static_reset('language_list');
    drupal_static_reset('language_url_outbound_alter');
    drupal_static_reset('language_url_rewrite_url');
  }

  /**
   * Check that non-installed languages are not considered.
   */
  function testUrlRewritingEdgeCases() {
    // Check URL rewriting with a non-installed language.
    $non_existing = language_default();
    $non_existing->langcode = $this->randomName();
    $this->checkUrl($non_existing, t('Path language is ignored if language is not installed.'), t('URL language negotiation does not work with non-installed languages'));
  }

  /**
   * Check URL rewriting for the given language.
   *
   * The test is performed with a fixed URL (the default front page) to simply
   * check that language prefixes are not added to it and that the prefixed URL
   * is actually not working.
   */
  private function checkUrl($language, $message1, $message2) {
    $options = array('language' => $language, 'script' => '');
    $base_path = trim(base_path(), '/');
    $rewritten_path = trim(str_replace($base_path, '', url('node', $options)), '/');
    $segments = explode('/', $rewritten_path, 2);
    $prefix = $segments[0];
    $path = isset($segments[1]) ? $segments[1] : $prefix;

    // If the rewritten URL has not a language prefix we pick a random prefix so
    // we can always check the prefixed URL.
    $prefixes = language_negotiation_url_prefixes();
    $stored_prefix = isset($prefixes[$language->langcode]) ? $prefixes[$language->langcode] : $this->randomName();
    if ($this->assertNotEqual($stored_prefix, $prefix, $message1)) {
      $prefix = $stored_prefix;
    }

    $this->drupalGet("$prefix/$path");
    $this->assertResponse(404, $message2);
  }

  /**
   * Check URL rewriting when using a domain name and a non-standard port.
   */
  function testDomainNameNegotiationPort() {
    $language_domain = 'example.fr';
    $edit = array(
      'language_negotiation_url_part' => 1,
      'domain[fr]' => $language_domain
    );
    $this->drupalPost('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Enable domain configuration.
    variable_set('language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_DOMAIN);

    // Reset static caching.
    drupal_static_reset('language_list');
    drupal_static_reset('language_url_outbound_alter');
    drupal_static_reset('language_url_rewrite_url');

    // Fake a different port.
    $_SERVER['HTTP_HOST'] .= ':88';

    // Create an absolute French link.
    $language = language_load('fr');
    $url = url('', array('absolute' => TRUE, 'language' => $language));

    $this->assertTrue(strcmp($url, 'http://example.fr:88/') == 0, 'The right port is used.');
  }

}
