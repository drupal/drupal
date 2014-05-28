<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageSwitchingTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the language switching feature.
 */
class LanguageSwitchingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'block', 'language_test');

  public static function getInfo() {
    return array(
      'name' => 'Language switching',
      'description' => 'Tests for the language switching feature.',
      'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Functional tests for the language switcher block.
   */
  function testLanguageBlock() {
    // Enable the language switching block..
    $block = $this->drupalPlaceBlock('language_block:' . Language::TYPE_INTERFACE, array(
      'id' => 'test_language_block',
      // Ensure a 2-byte UTF-8 sequence is in the tested output.
      'label' => $this->randomName(8) . '×',
    ));

    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

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
    list($language_switcher) = $this->xpath('//div[@id=:id]/div[contains(@class, "content")]', array(':id' => 'block-test-language-block'));
    $list_items = array();
    $anchors = array();
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
    $settings = $this->drupalGetSettings();
    $this->assertIdentical($settings['path']['currentPath'], 'user/2', 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['currentLanguage'], 'en', 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');
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
    list($language_switcher) = $this->xpath('//div[@id=:id]/div[contains(@class, "content")]', array(':id' => 'block-test-language-block'));
    $links = array(
      'active' => array(),
      'inactive' => array(),
    );
    $anchors = array(
      'active' => array(),
      'inactive' => array(),
    );
    foreach ($language_switcher->ul->li as $link) {
      $classes = explode(" ", (string) $link['class']);
      list($langcode) = array_intersect($classes, array('en', 'fr'));
      if (in_array('active', $classes)) {
        $links['active'][] = $langcode;
      }
      else {
        $links['inactive'][] = $langcode;
      }
      $anchor_classes = explode(" ", (string) $link->a['class']);
      if (in_array('active', $anchor_classes)) {
        $anchors['active'][] = $langcode;
      }
      else {
        $anchors['inactive'][] = $langcode;
      }
    }
    $this->assertIdentical($links, array('active' => array('en'), 'inactive' => array('fr')), 'Only the current language list item is marked as active on the language switcher block.');
    $this->assertIdentical($anchors, array('active' => array('en'), 'inactive' => array('fr')), 'Only the current language anchor is marked as active on the language switcher block.');
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
   * For authenticated users, the "active" class is set by JavaScript.
   *
   * @see testLanguageLinkActiveClass()
   */
  protected function doTestLanguageLinkActiveClassAuthenticated() {
    $function_name = '#type link';
    $path = 'language_test/type-link-active-class';

    // Test links generated by l() on an English page.
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
    $settings = $this->drupalGetSettings();
    $this->assertIdentical($settings['path']['currentPath'], $path, 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertIdentical($settings['path']['currentLanguage'], 'en', 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');

    // Test links generated by l() on a French page.
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
    $settings = $this->drupalGetSettings();
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
    $path = 'language_test/type-link-active-class';

    $this->drupalLogout();

    // Test links generated by l() on an English page.
    $current_language = 'English';
    $this->drupalGet('language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'no_lang_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'en_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should not be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and not(contains(@class, :class))]', array(':id' => 'fr_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is NOT marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Test links generated by l() on a French page.
    $current_language = 'French';
    $this->drupalGet('fr/language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $langcode = 'none';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'no_lang_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'en' link should not be active.
    $langcode = 'en';
    $links = $this->xpath('//a[@id = :id and not(contains(@class, :class))]', array(':id' => 'en_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is NOT marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));

    // Language code 'fr' link should be active.
    $langcode = 'fr';
    $links = $this->xpath('//a[@id = :id and contains(@class, :class)]', array(':id' => 'fr_link', ':class' => 'active'));
    $this->assertTrue(isset($links[0]), t('A link generated by :function to the current :language page with langcode :langcode is marked active.', array(':function' => $function_name, ':language' => $current_language, ':langcode' => $langcode)));
  }

}
