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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to use advanced search.
   *
   * @var \Drupal\user\UserInterface
   */
  public $testUser;

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    node_access_rebuild();

    // Create a test user and log in.
    $this->testUser = $this->drupalCreateUser([
      'access content',
      'search content',
      'use advanced search',
      'access user profiles',
    ]);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that search returns results with diacritics in the search phrase.
   */
  public function testPhraseSearchPunctuation() {
    // cSpell:disable
    $body_text = 'The Enricþment Center is cómmīŦŧęđ to the well BɆĬŇĜ of æll påŔťıçȉpǎǹţș. ';
    $body_text .= 'Also meklēt (see #731298)';
    $this->drupalCreateNode(['body' => [['value' => $body_text]]]);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    $edit = ['keys' => 'meklet'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = ['keys' => 'meklēt'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>meklēt</strong>');

    $edit = ['keys' => 'cómmīŦŧęđ BɆĬŇĜ påŔťıçȉpǎǹţș'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = ['keys' => 'committed being participants'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>cómmīŦŧęđ</strong>');
    $this->assertRaw('<strong>BɆĬŇĜ</strong>');
    $this->assertRaw('<strong>påŔťıçȉpǎǹţș</strong>');

    $edit = ['keys' => 'Enricþment'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>Enricþment</strong>');

    $edit = ['keys' => 'Enritchment'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertNoRaw('<strong>Enricþment</strong>');

    $edit = ['keys' => 'æll'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertRaw('<strong>æll</strong>');

    $edit = ['keys' => 'all'];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'Search');
    $this->assertNoRaw('<strong>æll</strong>');
    // cSpell:enable
  }

}
