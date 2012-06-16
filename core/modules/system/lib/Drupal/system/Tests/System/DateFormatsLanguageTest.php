<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\DateFormatsLanguageTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for localizing date formats.
 */
class DateFormatsLanguageTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Localize date formats',
      'description' => 'Tests for the localization of date formats.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp(array('node', 'language'));

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
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Set language negotiation.
    $language_type = LANGUAGE_TYPE_INTERFACE;
    $edit = array(
      "{$language_type}[enabled][language-url]" => TRUE,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Configure date formats.
    $this->drupalGet('admin/config/regional/date-time/locale');
    $this->assertText('French', 'Configured languages appear.');
    $edit = array(
      'date_format_long' => 'd.m.Y - H:i',
      'date_format_medium' => 'd.m.Y - H:i',
      'date_format_short' => 'd.m.Y - H:i',
    );
    $this->drupalPost('admin/config/regional/date-time/locale/fr/edit', $edit, t('Save configuration'));
    $this->assertText(t('Configuration saved.'), 'French date formats updated.');
    $edit = array(
      'date_format_long' => 'j M Y - g:ia',
      'date_format_medium' => 'j M Y - g:ia',
      'date_format_short' => 'j M Y - g:ia',
    );
    $this->drupalPost('admin/config/regional/date-time/locale/en/edit', $edit, t('Save configuration'));
    $this->assertText(t('Configuration saved.'), 'English date formats updated.');

    // Create node content.
    $node = $this->drupalCreateNode(array('type' => 'article'));

    // Configure format for the node posted date changes with the language.
    $this->drupalGet('node/' . $node->nid);
    $english_date = format_date($node->created, 'custom', 'j M Y');
    $this->assertText($english_date, t('English date format appears'));
    $this->drupalGet('fr/node/' . $node->nid);
    $french_date = format_date($node->created, 'custom', 'd.m.Y');
    $this->assertText($french_date, t('French date format appears'));
  }
}
