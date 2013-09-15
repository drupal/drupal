<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\DateFormatsLanguageTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for localizing date formats.
 */
class DateFormatsLanguageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'locale');

  public static function getInfo() {
    return array(
      'name' => 'Localize date formats',
      'description' => 'Tests for the localization of date formats.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages', 'create article content'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Functional tests for localizing date formats.
   */
  function testLocalizeDateFormats() {
    // Add language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Set language negotiation.
    $language_type = Language::TYPE_INTERFACE;
    $edit = array(
      "{$language_type}[enabled][language-url]" => TRUE,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Add new date format for French.
    $edit = array(
      'id' => 'example_style_fr',
      'label' => 'Example Style',
      'date_format_pattern' => 'd.m.Y - H:i',
      'locales[]' => array('fr'),
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Add new date format for English.
    $edit = array(
      'id' => 'example_style_en',
      'label' => 'Example Style',
      'date_format_pattern' => 'j M Y - g:ia',
      'locales[]' => array('en'),
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Configure date formats.
    $this->drupalGet('admin/config/regional/date-time/locale');
    $this->assertText('French', 'Configured languages appear.');
    $edit = array(
      'date_format_long' => 'example_style_fr',
      'date_format_medium' => 'example_style_fr',
      'date_format_short' => 'example_style_fr',
    );
    $this->drupalPostForm('admin/config/regional/date-time/locale/fr/edit', $edit, t('Save configuration'));
    $this->assertText(t('Configuration saved.'), 'French date formats updated.');

    $edit = array(
      'date_format_long' => 'example_style_en',
      'date_format_medium' => 'example_style_en',
      'date_format_short' => 'example_style_en',
    );
    $this->drupalPostForm('admin/config/regional/date-time/locale/en/edit', $edit, t('Save configuration'));
    $this->assertText(t('Configuration saved.'), 'English date formats updated.');

    // Create node content.
    $node = $this->drupalCreateNode(array('type' => 'article'));

    // Configure format for the node posted date changes with the language.
    $this->drupalGet('node/' . $node->id());
    $english_date = format_date($node->getCreatedTime(), 'custom', 'j M Y');
    $this->assertText($english_date, 'English date format appears');
    $this->drupalGet('fr/node/' . $node->id());
    $french_date = format_date($node->getCreatedTime(), 'custom', 'd.m.Y');
    $this->assertText($french_date, 'French date format appears');

    // Make sure we can reset dates back to default.
    $this->drupalPostForm('admin/config/regional/date-time/locale/en/reset', array(), t('Reset'));
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($english_date, 'English date format does not appear');
  }
}
