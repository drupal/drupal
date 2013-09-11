<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchLanguageTest.
 */

namespace Drupal\search\Tests;

/**
 * Test node search with multiple languages.
 */
class SearchLanguageTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Search language selection',
      'description' => 'Tests advanced search with different languages enabled.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $test_user = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search', 'administer nodes', 'administer languages', 'access administration pages', 'administer site configuration'));
    $this->drupalLogin($test_user);
  }

  function testLanguages() {
    // Add predefined language.
    $edit = array('predefined_langcode' => 'fr');
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', 'Language added successfully.');

    // Now we should have languages displayed.
    $this->drupalGet('search/node');
    $this->assertText(t('Languages'), 'Languages displayed to choose from.');
    $this->assertText(t('English'), 'English is a possible choice.');
    $this->assertText(t('French'), 'French is a possible choice.');

    // Ensure selecting no language does not make the query different.
    $this->drupalPostForm('search/node', array(), t('Advanced search'));
    $this->assertEqual($this->getUrl(), url('search/node/', array('absolute' => TRUE)), 'Correct page redirection, no language filtering.');

    // Pick French and ensure it is selected.
    $edit = array('language[fr]' => TRUE);
    $this->drupalPostForm('search/node', $edit, t('Advanced search'));
    // Get the redirected URL.
    $url = $this->getUrl();
    $parts = parse_url($url);
    $query_string = isset($parts['query']) ? rawurldecode($parts['query']) : '';
    $this->assertTrue(strpos($query_string, '=language:fr') !== FALSE, 'Language filter language:fr add to the query string.');

    // Change the default language and delete English.
    $path = 'admin/config/regional/settings';
    $this->drupalGet($path);
    $this->assertOptionSelected('edit-site-default-language', 'en', 'Default language updated.');
    $edit = array(
      'site_default_language' => 'fr',
    );
    $this->drupalPostForm($path, $edit, t('Save configuration'));
    $this->assertNoOptionSelected('edit-site-default-language', 'en', 'Default language updated.');
    $this->drupalPostForm('admin/config/regional/language/delete/en', array(), t('Delete'));
  }
}
