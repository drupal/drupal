<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests search functionality with diacritics.
 *
 * @group search
 */
class SearchNodeDiacriticsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search'];

  /**
   * A user with permission to use advanced search.
   *
   * @var \Drupal\user\UserInterface
   */
  public $testUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    node_access_rebuild();

    // Create a test user and log in.
    $this->testUser = $this->drupalCreateUser(['access content', 'search content', 'use advanced search', 'access user profiles']);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that search returns results with diacritics in the search phrase.
   */
  public function testPhraseSearchPunctuation() {
    $body_text = 'The Enricþment Center is cómmīŦŧęđ to the well BɆĬŇĜ of æll påŔťıçȉpǎǹţș. ';
    $body_text .= 'Also meklēt (see #731298)';
    $this->drupalCreateNode(['body' => [['value' => $body_text]]]);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    $edit = ['keys' => 'meklet'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = ['keys' => 'meklēt'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = ['keys' => 'cómmīŦŧęđ BɆĬŇĜ påŔťıçȉpǎǹţș'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = ['keys' => 'committed being participants'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = ['keys' => 'Enricþment'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>Enricþment</strong>');

    $edit = ['keys' => 'Enritchment'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>Enricþment</strong>');

    $edit = ['keys' => 'æll'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw('<strong>æll</strong>');

    $edit = ['keys' => 'all'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>æll</strong>');
  }

}
