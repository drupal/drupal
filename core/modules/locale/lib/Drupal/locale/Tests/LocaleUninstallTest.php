<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleUninstallTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Component\Utility\String;

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
    $config = \Drupal::config('locale.settings');
    $language = new Language(array(
      'id' => 'fr',
      'name' => 'French',
      'default' => $this->langcode == 'fr',
    ));
    language_save($language);
    // Reset the language manager.
    $language_manager = $this->container->get('language_manager');
    $language_manager->reset();
    $language_manager->init();
    // Check the UI language.

    // @todo: If the global user is an EntityBCDecorator, getting the roles
    // from it within LocaleLookup results in a loop that invokes LocaleLookup
    // again.
    global $user;
    $user = drupal_anonymous_user();

    $this->assertEqual(language(Language::TYPE_INTERFACE)->id, $this->langcode, String::format('Current language: %lang', array('%lang' => language(Language::TYPE_INTERFACE)->id)));

    // Enable multilingual workflow option for articles.
    language_save_default_configuration('node', 'article', array('langcode' => 'site_default', 'language_show' => TRUE));
    // Change JavaScript translations directory.
    $config->set('javascript.directory', 'js_translations')->save();
    // Build the JavaScript translation file for French.
    $user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/regional/translate');
    // Get any of the javascript strings to translate.
    $js_strings = $this->container->get('locale.storage')->getStrings(array('type' => 'javascript'));
    $string = reset($js_strings);
    $edit = array('string' => $string->source);
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Filter'));
    $edit = array('strings[' . $string->lid . '][translations][0]' => 'french translation');
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    _locale_rebuild_js('fr');
    $config = \Drupal::config('locale.settings');
    $locale_javascripts = $this->container->get('state')->get('locale.translation.javascript') ?: array();
    $js_file = 'public://' . $config->get('javascript.directory') . '/fr_' . $locale_javascripts['fr'] . '.js';
    $this->assertTrue($result = file_exists($js_file), String::format('JavaScript file created: %file', array('%file' => $result ? $js_file : 'none')));

    // Disable string caching.
    $config->set('cache_strings', 0)->save();

    // Change language negotiation options.
    drupal_load('module', 'locale');
    \Drupal::config('system.language.types')->set('configurable', language_types_get_default() + array('language_custom' => TRUE))->save();
    variable_set('language_negotiation_' . Language::TYPE_INTERFACE, language_language_negotiation_info());
    variable_set('language_negotiation_' . Language::TYPE_CONTENT, language_language_negotiation_info());
    variable_set('language_negotiation_' . Language::TYPE_URL, language_language_negotiation_info());

    // Change language negotiation settings.
    \Drupal::config('language.negotiation')
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
    $this->assertEqual(language(Language::TYPE_INTERFACE)->id, 'en', String::format('Language after uninstall: %lang', array('%lang' => language(Language::TYPE_INTERFACE)->id)));

    // Check JavaScript files deletion.
    $this->assertTrue($result = !file_exists($js_file), String::format('JavaScript file deleted: %file', array('%file' => $result ? $js_file : 'found')));

    // Check language count.
    $language_count = $this->container->get('state')->get('language_count') ?: 1;
    $this->assertEqual($language_count, 1, String::format('Language count: %count', array('%count' => $language_count)));

    // Check language negotiation.
    require_once DRUPAL_ROOT . '/core/includes/language.inc';
    $this->assertTrue(count(language_types_get_all()) == count(language_types_get_default()), 'Language types reset');
    $language_negotiation = language_negotiation_method_get_first(Language::TYPE_INTERFACE) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, String::format('Interface language negotiation: %setting', array('%setting' => $language_negotiation ? 'none' : 'set')));
    $language_negotiation = language_negotiation_method_get_first(Language::TYPE_CONTENT) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, String::format('Content language negotiation: %setting', array('%setting' => $language_negotiation ? 'none' : 'set')));
    $language_negotiation = language_negotiation_method_get_first(Language::TYPE_URL) == LANGUAGE_NEGOTIATION_SELECTED;
    $this->assertTrue($language_negotiation, String::format('URL language negotiation: %setting', array('%setting' => $language_negotiation ? 'none' : 'set')));

    // Check language negotiation method settings.
    $this->assertFalse(\Drupal::config('language.negotiation')->get('url.source'), 'URL language negotiation method indicator settings cleared.');
    $this->assertFalse(\Drupal::config('language.negotiation')->get('session.parameter'), 'Visit language negotiation method settings cleared.');

    // Check JavaScript parsed.
    $javascript_parsed_count = count($this->container->get('state')->get('system.javascript_parsed') ?: array());
    $this->assertEqual($javascript_parsed_count, 0, String::format('JavaScript parsed count: %count', array('%count' => $javascript_parsed_count)));
  }
}
