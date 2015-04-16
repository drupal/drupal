<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageSwitchingTest.
 */

namespace Drupal\language\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the language switching feature.
 *
 * @group language
 */
class LanguageSwitchingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'language', 'block', 'language_test');

  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Functional tests for the language switcher block.
   */
  function testLanguageBlock() {
    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Set the native language name.
    $this->saveNativeLanguageName('fr', 'français');

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Enable the language switching block.
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, array(
      'id' => 'test_language_block',
      // Ensure a 2-byte UTF-8 sequence is in the tested output.
      'label' => $this->randomMachineName(8) . '×',
    ));

    $this->doTestLanguageBlockAuthenticated($block->label());
    $this->doTestLanguageBlockAnonymous($block->label());
  }

  /**
   * For authenticated users, the "active" class is set by JavaScript.
   *
   * @param string $block_label
   *   The label of the language switching block.
   *
   * @see testLanguageBlock()
   */
  protected function doTestLanguageBlockAuthenticated($block_label) {
    // Assert that the language switching block is displayed on the frontpage.
    $this->drupalGet('');
    $this->assertText($block_label, 'Language switcher block found.');

    // Assert that each list item and anchor element has the appropriate data-
    // attributes.
    list($language_switcher) = $this->xpath('//div[@id=:id]', array(':id' => 'block-test-language-block'));
    $list_items = array();
    $anchors = array();
    $labels = array();
    foreach ($language_switcher->ul->li as $list_item) {
      $classes = explode(" ", (string) $list_item['class']);
      list($langcode) = array_intersect($classes, array('en', 'fr'));
      $list_items[] = array(
        'langcode_class' => $langcode,
        'data-drupal-link-system-path' => (string) $list_item['data-drupal-link-system-path'],
      );
      $anchors[] = array(
        'hreflang' => (string) $list_item->a['hreflang'],
        'data-drupal-link-system-path' => (string) $list_item->a['data-drupal-link-system-path'],
      );
      $labels[] = (string) $list_item->a;
    }
    $expected_list_items = array(
      0 => array('langcode_class' => 'en', 'data-drupal-link-system-path' => 'user/2'),
      1 => array('langcode_class' => 'fr', 'data-drupal-link-system-path' => 'user/2'),
    );
    $this->assertIdentical($list_items, $expected_list_items, 'The list items have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $expected_anchors = array(
      0 => array('hreflang' => 'en', 'data-drupal-link-system-path' => 'user/2'),
      1 => array('hreflang' => 'fr', 'data-drupal-link-system-path' => 'user/2'),
    );
    $this->assertIdentical($anchors, $expected_anchors, 'The anchors have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $settings = $this->getDrupalSettings();
    $this->assertIdentical($settings['path']['currentPath'], 'user/2', 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['currentLanguage'], 'en', 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($labels, array('English', 'français'), 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * For anonymous users, the "active" class is set by PHP.
   *
   * @param string $block_label
   *   The label of the language switching block.
   *
   * @see testLanguageBlock()
   */
  protected function doTestLanguageBlockAnonymous($block_label) {
    $this->drupalLogout();

    // Assert that the language switching block is displayed on the frontpage.
    $this->drupalGet('');
    $this->assertText($block_label, 'Language switcher block found.');

    // Assert that only the current language is marked as active.
    list($language_switcher) = $this->xpath('//div[@id=:id]', array(':id' => 'block-test-language-block'));
    $links = array(
      'active' => array(),
      'inactive' => array(),
    );
    $anchors = array(
      'active' => array(),
      'inactive' => array(),
    );
    $labels = array();
    foreach ($language_switcher->ul->li as $link) {
      $classes = explode(" ", (string) $link['class']);
      list($langcode) = array_intersect($classes, array('en', 'fr'));
      if (in_array('is-active', $classes)) {
        $links['active'][] = $langcode;
      }
      else {
        $links['inactive'][] = $langcode;
      }
      $anchor_classes = explode(" ", (string) $link->a['class']);
      if (in_array('is-active', $anchor_classes)) {
        $anchors['active'][] = $langcode;
      }
      else {
        $anchors['inactive'][] = $langcode;
      }
      $labels[] = (string) $link->a;
    }
    $this->assertIdentical($links, array('active' => array('en'), 'inactive' => array('fr')), 'Only the current language list item is marked as active on the language switcher block.');
    $this->assertIdentical($anchors, array('active' => array('en'), 'inactive' => array('fr')), 'Only the current language anchor is marked as active on the language switcher block.');
    $this->assertIdentical($labels, array('English', 'français'), 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * Test language switcher links for domain based negotiation.
   */
  function testLanguageBlockWithDomain() {
    // Add the Italian language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Rebuild the container so that the new language is picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();

    $languages = $this->container->get('language_manager')->getLanguages();

    // Enable browser and URL language detection.
    $edit = array(
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Do not allow blank domain.
    $edit = array(
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[en]' => '',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));
    $this->assertText(t('The domain may not be left blank for English'), 'The form does not allow blank domains.');

    // Change the domain for the Italian language.
    $edit = array(
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[en]' => \Drupal::request()->getHost(),
      'domain[it]' => 'it.example.com',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved'), 'Domain configuration is saved.');

    // Enable the language switcher block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, array('id' => 'test_language_block'));

    $this->drupalGet('');

    /** @var \Drupal\Core\Routing\UrlGenerator $generator */
    $generator = $this->container->get('url_generator');

    // Verfify the English URL is correct
    list($english_link) = $this->xpath('//div[@id=:id]/ul/li/a[@hreflang=:hreflang]', array(
      ':id' => 'block-test-language-block',
      ':hreflang' => 'en',
    ));
    $english_url = $generator->generateFromPath('user/2', array('language' => $languages['en']));
    $this->assertEqual($english_url, (string) $english_link['href']);

    // Verfify the Italian URL is correct
    list($italian_link) = $this->xpath('//div[@id=:id]/ul/li/a[@hreflang=:hreflang]', array(
      ':id' => 'block-test-language-block',
      ':hreflang' => 'it',
    ));
    $italian_url = $generator->generateFromPath('user/2', array('language' => $languages['it']));
    $this->assertEqual($italian_url, (string) $italian_link['href']);
  }

  /**
   * Test active class on links when switching languages.
   */
  function testLanguageLinkActiveClass() {
    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->doTestLanguageLinkActiveClassAuthenticated();
    $this->doTestLanguageLinkActiveClassAnonymous();
  }

  /**
   * Check the path-admin class, as same as on default language.
   */
  function testLanguageBodyClass() {
    $searched_class = 'path-admin';

    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Check if the default (English) admin/config page has the right class.
    $this->drupalGet('admin/config');
    $class = $this->xpath('//body[contains(@class, :class)]', array(':class' => $searched_class));
    $this->assertTrue(isset($class[0]), t('The path-admin class appears on default language.'));

    // Check if the French admin/config page has the right class.
    $this->drupalGet('fr/admin/config');
    $class = $this->xpath('//body[contains(@class, :class)]', array(':class' => $searched_class));
    $this->assertTrue(isset($class[0]), t('The path-admin class same as on default language.'));

    // The testing profile sets the user/login page as the frontpage. That
    // redirects authenticated users to their profile page, so check with an
    // anonymous user instead.
    $this->drupalLogout();

    // Check if the default (English) frontpage has the right class.
    $this->drupalGet('<front>');
    $class = $this->xpath('//body[contains(@class, :class)]', array(':class' => 'path-frontpage'));
    $this->assertTrue(isset($class[0]), 'path-frontpage class found on the body tag');

    // Check if the French frontpage has the right class.
    $this->drupalGet('fr');
    $class = $this->xpath('//body[contains(@class, :class)]', array(':class' => 'path-frontpage'));
    $this->assertTrue(isset($class[0]), 'path-frontpage class found on the body tag with french as the active language');

  }

  /**
   * For authenticated users, the "active" class is set by JavaScript.
   *
   * @see testLanguageLinkActiveClass()
   */
  protected function doTestLanguageLinkActiveClassAuthenticated() {
    $function_name = '#type link';
    $path = 'language_test/type-link-active-class';

    // Test links generated by _l() on an English page.
    $current_language = 'English';
    $this->drupalGet($path);

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and @data-drupal-link-system-path = :path]', array(':id' => 'no_lang_link', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and @hreflang = :lang and @data-drupal-link-system-path = :path]', array(':id' => 'en_link', ':lang' => 'en', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should not be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and @hreflang = :lang and @data-drupal-link-system-path = :path]', array(':id' => 'fr_link', ':lang' => 'fr', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to NOT mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Verify that drupalSettings contains the correct values.
    $settings = $this->getDrupalSettings();
    $this->assertIdentical($settings['path']['currentPath'], $path, 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['currentLanguage'], 'en', 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');

    // Test links generated by _l() on a French page.
    $current_language = 'French';
    $this->drupalGet('fr/language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and @data-drupal-link-system-path = :path]', array(':id' => 'no_lang_link', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should not be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and @hreflang = :lang and @data-drupal-link-system-path = :path]', array(':id' => 'en_link', ':lang' => 'en', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to NOT mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and @hreflang = :lang and @data-drupal-link-system-path = :path]', array(':id' => 'fr_link', ':lang' => 'fr', ':path' => $path));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode has the correct attributes that will allow the drupal.active-link library to mark it as active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Verify that drupalSettings contains the correct values.
    $settings = $this->getDrupalSettings();
    $this->assertIdentical($settings['path']['currentPath'], $path, 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['currentLanguage'], 'fr', 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');
  }

  /**
   * For anonymous users, the "active" class is set by PHP.
   *
   * @see testLanguageLinkActiveClass()
   */
  protected function doTestLanguageLinkActiveClassAnonymous() {
    $function_name = '#type link';

    $this->drupalLogout();

    // Test links generated by _l() on an English page.
    $current_language = 'English';
    $this->drupalGet('language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'no_lang_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'en_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should not be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and not(contains(@class, :class))]', array(':id' => 'fr_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is NOT marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Test links generated by _l() on a French page.
    $current_language = 'French';
    $this->drupalGet('fr/language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'no_lang_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should not be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and not(contains(@class, :class))]', array(':id' => 'en_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is NOT marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'fr_link', ':class' => 'is-active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));
  }

  /**
   * Saves the native name of a language entity in configuration as a label.
   *
   * @param string $langcode
   *   The language code of the language.
   * @param string $label
   *   The native name of the language.
   */
  protected function saveNativeLanguageName($langcode, $label) {
    \Drupal::service('language.config_factory_override')
      ->getOverride($langcode, 'language.entity.' . $langcode)->set('label', $label)->save();
  }

}
