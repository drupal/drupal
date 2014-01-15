<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleUninstallTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

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
    // Pick only core language types.
    $language_manager = new LanguageManager();
    $default_types = $language_manager->getLanguageTypes();
    \Drupal::config('language.types')->set('configurable', $default_types + array('language_custom' => TRUE))->save();
    $config = array_flip(array_keys(\Drupal::service('plugin.manager.language_negotiation_method')->getDefinitions()));
    variable_set('language_negotiation_' . Language::TYPE_INTERFACE, $config);
    variable_set('language_negotiation_' . Language::TYPE_CONTENT, $config);
    variable_set('language_negotiation_' . Language::TYPE_URL, $config);

    // Change language negotiation settings.
    \Drupal::config('language.negotiation')
      ->set('url.source', LanguageNegotiationUrl::CONFIG_PATH_PREFIX)
      ->set('session.parameter', TRUE)
      ->save();

    // Uninstall Locale.
    module_uninstall($locale_module);
    $this->rebuildContainer();

    // Visit the front page.
    $this->drupalGet('');
    // Check the init language logic.
    $this->assertEqual(language(Language::TYPE_INTERFACE)->id, 'en', String::format('Language after uninstall: %lang', array('%lang' => language(Language::TYPE_INTERFACE)->id)));

    // Check JavaScript files deletion.
    $this->assertTrue($result = !file_exists($js_file), String::format('JavaScript file deleted: %file', array('%file' => $result ? $js_file : 'found')));

    // Check language negotiation.
    try {
      $message = 'Language negotiation is not available.';
      $this->assertTrue(count($this->container->get('language_manager')->getLanguageTypes()) == count($default_types), 'Language types reset');
      \Drupal::service('language_negotiator');
      $this->fail($message);
    }
    catch (InvalidArgumentException $e) {
      $this->pass($message);
    }

    // Check language negotiation method settings.
    $this->assertFalse(\Drupal::config('language.negotiation')->get('url.source'), 'URL language negotiation method indicator settings cleared.');
    $this->assertFalse(\Drupal::config('language.negotiation')->get('session.parameter'), 'Visit language negotiation method settings cleared.');

    // Check JavaScript parsed.
    $javascript_parsed_count = count($this->container->get('state')->get('system.javascript_parsed') ?: array());
    $this->assertEqual($javascript_parsed_count, 0, String::format('JavaScript parsed count: %count', array('%count' => $javascript_parsed_count)));
  }
}
