<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchNodePunctuationTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests search functionality with punctuation.
 *
 * @group search
 */
class SearchNodePunctuationTest extends SearchTestBase {

  /**
   * A user with permission to use advanced search.
   *
   * @var \Drupal\user\UserInterface
   */
  public $testUser;

  protected function setUp() {
    parent::setUp();
    node_access_rebuild();

    // Create a test user and log in.
    $this->testUser = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search'));
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that search returns results with punctuation in the search phrase.
   */
  function testPhraseSearchPunctuation() {
    $node = $this->drupalCreateNode(array('body' => array(array('value' => "The bunny's ears were fluffy."))));

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Submit a phrase wrapped in double quotes to include the punctuation.
    $edit = array('keys' => '"bunny\'s"');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText($node->label());
  }
}
