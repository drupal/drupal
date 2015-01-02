<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchLanguageTest.
 */

namespace Drupal\search\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests advanced search with different languages added.
 *
 * @group search
 */
class SearchLanguageTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Array of nodes available to search.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $searchableNodes;

  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $test_user = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search', 'administer nodes', 'administer languages', 'access administration pages', 'administer site configuration'));
    $this->drupalLogin($test_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the body field translatable. The title is already translatable by
    // definition. The parent class has already created the article and page
    // content types.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->translatable = TRUE;
    $field_storage->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = array(
      array(
        'title' => 'First node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'title' => 'Second node this is the Spanish title',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'es',
      ),
      array(
        'title' => 'Third node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
    );
    $this->searchableNodes = [];
    foreach ($nodes as $setting) {
      $this->searchableNodes[] = $this->drupalCreateNode($setting);
    }

    // Add English translation to the second node.
    $translation = $this->searchableNodes[1]->addTranslation('en', array('title' => 'Second node en'));
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[1]->save();

    // Add Spanish translation to the third node.
    $translation = $this->searchableNodes[2]->addTranslation('es', array('title' => 'Third node es'));
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[2]->save();

    // Update the index and then run the shutdown method.
    $plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $plugin->updateIndex();
    search_update_totals();
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
    $this->assertUrl(\Drupal::url('search.view_node_search', [], ['query' => ['keys' => ''], 'absolute' => TRUE]), [], 'Correct page redirection, no language filtering.');

    // Pick French and ensure it is selected.
    $edit = array('language[fr]' => TRUE);
    $this->drupalPostForm('search/node', $edit, t('Advanced search'));
    // Get the redirected URL.
    $url = $this->getUrl();
    $parts = parse_url($url);
    $query_string = isset($parts['query']) ? rawurldecode($parts['query']) : '';
    $this->assertTrue(strpos($query_string, '=language:fr') !== FALSE, 'Language filter language:fr add to the query string.');

    // Search for keyword node and language filter as Spanish.
    $edit = array('keys' => 'node', 'language[es]' => TRUE);
    $this->drupalPostForm('search/node', $edit, t('Advanced search'));
    // Check for Spanish results.
    $this->assertLink('Second node this is the Spanish title', 0, 'Second node Spanish title found in search results');
    $this->assertLink('Third node es', 0, 'Third node Spanish found in search results');
    // Ensure that results doesn't contain other language nodes.
    $this->assertNoLink('First node en', 'Search results does not contain first English node');
    $this->assertNoLink('Second node en', 'Search results does not contain second English node');
    $this->assertNoLink('Third node en', 'Search results does not contain third English node');

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
