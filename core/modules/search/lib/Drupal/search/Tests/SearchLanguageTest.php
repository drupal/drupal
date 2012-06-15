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
  public static function getInfo() {
    return array(
      'name' => 'Search language selection',
      'description' => 'Tests advanced search with different languages enabled.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp(array('language'));

    // Create and login user.
    $test_user = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search', 'administer nodes', 'administer languages', 'access administration pages'));
    $this->drupalLogin($test_user);
  }

  function testLanguages() {
    // Add predefined language.
    $edit = array('predefined_langcode' => 'fr');
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', t('Language added successfully.'));

    // Now we should have languages displayed.
    $this->drupalGet('search/node');
    $this->assertText(t('Languages'), t('Languages displayed to choose from.'));
    $this->assertText(t('English'), t('English is a possible choice.'));
    $this->assertText(t('French'), t('French is a possible choice.'));

    // Ensure selecting no language does not make the query different.
    $this->drupalPost('search/node', array(), t('Advanced search'));
    $this->assertEqual($this->getUrl(), url('search/node/', array('absolute' => TRUE)), t('Correct page redirection, no language filtering.'));

    // Pick French and ensure it is selected.
    $edit = array('language[fr]' => TRUE);
    $this->drupalPost('search/node', $edit, t('Advanced search'));
    $this->assertFieldByXPath('//input[@name="keys"]', 'language:fr', t('Language filter added to query.'));

    // Change the default language and delete English.
    $path = 'admin/config/regional/language';
    $this->drupalGet($path);
    $this->assertFieldChecked('edit-site-default-en', t('English is the default language.'));
    $edit = array('site_default' => 'fr');
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertNoFieldChecked('edit-site-default-en', t('Default language updated.'));
    $this->drupalPost('admin/config/regional/language/delete/en', array(), t('Delete'));
  }
}
