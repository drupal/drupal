<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleUninstallTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Locale uninstall with English UI functional test.
 */
class LocaleUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'locale');

  public static function getInfo() {
    return array(
      'name' => 'Locale uninstall (EN)',
      'description' => 'Tests the uninstall process using the built-in UI language.',
      'group' => 'Locale',
    );
  }

  /**
   * The default language set for the UI before uninstall.
   */
  protected $language;

  function setUp() {
    parent::setUp();

    $this->langcode = 'en';

    // Create Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Check if the values of the Locale variables are correct after uninstall.
   */
  function testUninstallProcess() {
    $locale_module = array('locale', 'language');

    $language = new Language(array(
      'langcode' => 'fr',
      'name' => 'French',
      'default' => $this->langcode == 'fr',
    ));
    language_save($language);
    // Reset the language manager.
    $language_manager = $this->container->get('language_manager');
    $language_manager->reset();
    $language_manager->init();
    // Check the UI language.

    $this->assertEqual(language(LANGUAGE_TYPE_INTERFACE)->langcode, $this->langcode, t('Current language: %lang', array('%lang' => language(LANGUAGE_TYPE_INTERFACE)->langcode)));

    // Enable multilingual workflow option for articles.
    language_save_default_configuration('node', 'article', array('langcode' => 'site_default', 'language_show' => TRUE));
    // Change JavaScript translations directory.
    variable_set('locale_js_directory', 'js_translations');
    // Build the JavaScript translation file for French.
    $user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/regional/translate/translate');
    // Get any of the javascript strings to translate.
    $js_strings = locale_storage()->getStrings(array('type' => 'javascript'));
    $string = reset($js_strings);
    $edit = array('string' => $string->source);
    $this->drupalPost('admin/config/regional/translate', $edit, t('Filter'));
    $edit = array('strings[' . $string->lid . '][translations][0]' => 'french translation');
    $this->drupalPost('admin/config/regional/translate', $edit, t('Save translations'));
    _locale_rebuild_js('fr');
    $locale_javascripts = variable_get('locale_translation_javascript', array());
    $js_file = 'public://' . variable_get('locale_js_directory', 'languages') . '/fr_' . $locale_javascripts['fr'] . '.js';
    $this->assertTrue($result = file_exists($js_file), t('JavaScript file created: %file', array('%file' => $result ? $js_file : t('none'))));

    // Disable string caching.
    variable_set('locale_cache_strings', 0);

    // Change language negotiation options.
    drupal_load('module', 'locale');
    variable_set('language_types', language_types_get_default() + array('language_custom' => TRUE));
    variable_set('language_negotiation_' . LANGUAGE_TYPE_INTERFACE, language_language_negotiation_info());
    variable_set('language_negotiation_' . LANGUAGE_TYPE_CONTENT, language_language_negotiation_info());
    variable_set('language_negotiation_' . LANGUAGE_TYPE_URL, language_language_negotiation_info());

    // Change language negotiation settings.
    config('language.negotiation')
      ->set('url.source', LANGUAGE_NEGOTIATION_URL_PREFIX)
      ->set('session.parameter', TRUE)
      ->save();

    // Uninstall Locale.
    module_disable($locale_module);
    module_uninstall($locale_module);
    $this->rebuildContainer();

    // Visit the front page.
    $this->drupalGet('');
    // Check the init language logic.
    $this->assertEqual(language(LANGUAGE_TYPE_INTERFACE)->langcode, 'en', t('Language after uninstall: %lang', array('%lang' => language(LANGUAGE_TYPE_INTERFACE)->langcode)));

    // Check JavaScript files deletion.
    $this->assertTrue($result = !file_exists($js_file), t('JavaScript file deleted: %file', array('%file' => $result ? $js_file : t('found'))));

    // Check language count.
    $language_count = variable_get('language_count', 1);
    $this->assertEqual($language_count, 1, t('Language count: %count', array('%count' => $language_count)));

    // Check language negotiation.
    require_once DRUPAL_ROOT . '/core/includes/language.inc';
    $this->assertTrue(count(language_types_get_all()) == count(language_types_get_default()), t('Language types reset'));
    $language_negotiation = language_negotiation_method_get_first(LANGUAGE_TYPE_INTERFACE) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, t('Interface language negotiation: %setting', array('%setting' => t($language_negotiation ? 'none' : 'set'))));
    $language_negotiation = language_negotiation_method_get_first(LANGUAGE_TYPE_CONTENT) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, t('Content language negotiation: %setting', array('%setting' => t($language_negotiation ? 'none' : 'set'))));
    $language_negotiation = language_negotiation_method_get_first(LANGUAGE_TYPE_URL) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, t('URL language negotiation: %setting', array('%setting' => t($language_negotiation ? 'none' : 'set'))));

    // Check language negotiation method settings.
    $this->assertFalse(config('language.negotiation')->get('url.source'), t('URL language negotiation method indicator settings cleared.'));
    $this->assertFalse(config('language.negotiation')->get('session.parameter'), t('Visit language negotiation method settings cleared.'));

    // Check JavaScript parsed.
    $javascript_parsed_count = count(state()->get('system.javascript_parsed') ?: array());
    $this->assertEqual($javascript_parsed_count, 0, t('JavaScript parsed count: %count', array('%count' => $javascript_parsed_count)));

    // Check JavaScript translations directory.
    $locale_js_directory = variable_get('locale_js_directory', 'languages');
    $this->assertEqual($locale_js_directory, 'languages', t('JavaScript translations directory: %dir', array('%dir' => $locale_js_directory)));

    // Check string caching.
    $locale_cache_strings = variable_get('locale_cache_strings', 1);
    $this->assertEqual($locale_cache_strings, 1, t('String caching: %status', array('%status' => t($locale_cache_strings ? 'enabled': 'disabled'))));
  }
}
