<?php

namespace Drupal\search\Tests;

/**
 * Tests search functionality with diacritics.
 *
 * @group search
 */
class SearchNodeDiacriticsTest extends SearchTestBase {

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
    $this->testUser = $this->drupalCreateUser(array('access content', 'search content', 'use advanced search', 'access user profiles'));
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that search returns results with diacritics in the search phrase.
   */
  function testPhraseSearchPunctuation() {
    $body_text = 'The Enricþment Center is cómmīŦŧęđ to the well BɆĬŇĜ of æll påŔťıçȉpǎǹţș. ';
    $body_text .= 'Also meklēt (see #731298)';
    $this->drupalCreateNode(array('body' => array(array('value' => $body_text))));

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    $edit = array('keys' => 'meklet');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = array('keys' => 'meklēt');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = array('keys' => 'cómmīŦŧęđ BɆĬŇĜ påŔťıçȉpǎǹţș');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = array('keys' => 'committed being participants');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = array('keys' => 'Enricþment');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>Enricþment</strong>');

    $edit = array('keys' => 'Enritchment');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>Enricþment</strong>');

    $edit = array('keys' => 'æll');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>æll</strong>');

    $edit = array('keys' => 'all');
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>æll</strong>');
  }
}
