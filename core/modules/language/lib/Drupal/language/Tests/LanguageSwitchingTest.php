<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageSwitchingTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the language switching feature.
 */
class LanguageSwitchingTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Language switching',
      'description' => 'Tests for the language switching feature.',
      'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp(array('language', 'block'));

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Functional tests for the language switcher block.
   */
  function testLanguageBlock() {
    // Enable the language switching block.
    $language_type = LANGUAGE_TYPE_INTERFACE;
    $edit = array(
      "blocks[language_{$language_type}][region]" => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Assert that the language switching block is displayed on the frontpage.
    $this->drupalGet('');
    $this->assertText(t('Languages'), t('Language switcher block found.'));

    // Assert that only the current language is marked as active.
    list($language_switcher) = $this->xpath('//div[@id=:id]/div[@class="content"]', array(':id' => 'block-language-' . str_replace('_', '-', $language_type)));
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
    $this->assertIdentical($links, array('active' => array('en'), 'inactive' => array('fr')), t('Only the current language list item is marked as active on the language switcher block.'));
    $this->assertIdentical($anchors, array('active' => array('en'), 'inactive' => array('fr')), t('Only the current language anchor is marked as active on the language switcher block.'));
  }
}
