<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchExactTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests that searching for a phrase gets the correct page count.
 *
 * @group search
 */
class SearchExactTest extends SearchTestBase {
  /**
   * Tests that the correct number of pager links are found for both keywords and phrases.
   */
  function testExactQuery() {
    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('create page content', 'search content')));

    $settings = array(
      'type' => 'page',
      'title' => 'Simple Node',
    );
    // Create nodes with exact phrase.
    for ($i = 0; $i <= 17; $i++) {
      $settings['body'] = array(array('value' => 'love pizza'));
      $this->drupalCreateNode($settings);
    }
    // Create nodes containing keywords.
    for ($i = 0; $i <= 17; $i++) {
      $settings['body'] = array(array('value' => 'love cheesy pizza'));
      $this->drupalCreateNode($settings);
    }

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Test that the correct number of pager links are found for keyword search.
    $edit = array('keys' => 'love pizza');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertLinkByHref('page=1', 0, '2nd page link is found for keyword search.');
    $this->assertLinkByHref('page=2', 0, '3rd page link is found for keyword search.');
    $this->assertLinkByHref('page=3', 0, '4th page link is found for keyword search.');
    $this->assertNoLinkByHref('page=4', '5th page link is not found for keyword search.');

    // Test that the correct number of pager links are found for exact phrase search.
    $edit = array('keys' => '"love pizza"');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertLinkByHref('page=1', 0, '2nd page link is found for exact phrase search.');
    $this->assertNoLinkByHref('page=2', '3rd page link is not found for exact phrase search.');
  }
}
