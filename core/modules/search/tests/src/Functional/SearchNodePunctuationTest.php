<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests search functionality with punctuation and HTML entities.
 *
 * @group search
 */
class SearchNodePunctuationTest extends BrowserTestBase {

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
    $this->testUser = $this->drupalCreateUser(['access content', 'search content', 'use advanced search', 'access user profiles']);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that search works with punctuation and HTML entities.
   */
  public function testPhraseSearchPunctuation() {
    $node = $this->drupalCreateNode(['body' => [['value' => "The bunny's ears were fluffy."]]]);
    $node2 = $this->drupalCreateNode(['body' => [['value' => 'Dignissim Aliquam &amp; Quieligo meus natu quae quia te. Damnum&copy; erat&mdash; neo pneum. Facilisi feugiat ibidem ratis.']]]);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Submit a phrase wrapped in double quotes to include the punctuation.
    $edit = ['keys' => '"bunny\'s"'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText($node->label());

    // Check if the author is linked correctly to the user profile page.
    $username = $node->getOwner()->getAccountName();
    $this->assertSession()->linkExists($username);

    // Search for "&" and verify entities are not broken up in the output.
    $edit = ['keys' => '&'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>&</strong>amp;');
    $this->assertText('You must include at least one keyword');

    $edit = ['keys' => '&amp;'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoRaw('<strong>&</strong>amp;');
    $this->assertText('You must include at least one keyword');
  }

}
