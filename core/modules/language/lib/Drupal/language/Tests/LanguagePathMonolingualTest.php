<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguagePathMonolingualTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that paths are not prefixed on a monolingual site.
 */
class LanguagePathMonolingualTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'path');

  public static function getInfo() {
    return array(
      'name' => 'Paths on non-English monolingual sites',
      'description' => 'Confirm that paths are not changed on monolingual non-English sites',
      'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $web_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer site configuration'));
    $this->drupalLogin($web_user);

    // Enable French language.
    $edit = array();
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Make French the default language.
    $edit = array(
      'site_default_language' => 'fr',
    );
    $this->drupalpost('admin/config/regional/settings', $edit, t('Save configuration'));

    // Delete English.
    $this->drupalPost('admin/config/regional/language/delete/en', array(), t('Delete'));

    // Verify that French is the only language.
    $this->assertFalse(language_multilingual(), 'Site is mono-lingual');
    $this->assertEqual(language_default()->id, 'fr', 'French is the default language');

    // Set language detection to URL.
    $edit = array('language_interface[enabled][language-url]' => TRUE);
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Force languages to be initialized.
    drupal_language_initialize();
  }

  /**
   * Verifies that links do not have language prefixes in them.
   */
  function testPageLinks() {
    // Navigate to 'admin/config' path.
    $this->drupalGet('admin/config');

    // Verify that links in this page do not have a 'fr/' prefix.
    $this->assertNoLinkByHref('/fr/', 'Links do not contain language prefix');

    // Verify that links in this page can be followed and work.
    $this->clickLink(t('Languages'));
    $this->assertResponse(200, 'Clicked link results in a valid page');
    $this->assertText(t('Add language'), 'Page contains the add language text');
  }
}
