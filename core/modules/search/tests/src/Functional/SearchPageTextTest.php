<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the search help text and search page text.
 *
 * @group search
 */
class SearchPageTextTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to use advanced search.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create user.
    $this->searchingUser = $this->drupalCreateUser([
      'search content',
      'access user profiles',
      'use advanced search',
    ]);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests for XSS in search module local task.
   *
   * This is a regression test for https://www.drupal.org/node/2338081
   */
  public function testSearchLabelXSS() {
    $this->drupalLogin($this->drupalCreateUser(['administer search']));

    $keys['label'] = '<script>alert("Don\'t Panic");</script>';
    $this->drupalPostForm('admin/config/search/pages/manage/node_search', $keys, 'Save search page');

    $this->drupalLogin($this->searchingUser);
    $this->drupalGet('search/node');
    $this->assertSession()->assertEscaped($keys['label']);
  }

  /**
   * Tests the failed search text, and various other text on the search page.
   */
  public function testSearchText() {
    $this->drupalLogin($this->searchingUser);
    $this->drupalGet('search/node');
    $this->assertText('Enter your keywords');
    $this->assertText('Search');
    $this->assertSession()->titleEquals('Search | Drupal');

    $edit = [];
    $search_terms = 'bike shed ' . $this->randomMachineName();
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, 'Search');
    $this->assertText('search yielded no results');
    $this->assertText('Search');
    $title_source = 'Search for @keywords | Drupal';
    $this->assertSession()->titleEquals('Search for ' . Unicode::truncate($search_terms, 60, TRUE, TRUE) . ' | Drupal');
    $this->assertNoText('Node');
    $this->assertNoText('Node');
    $this->assertText('Content');

    $this->clickLink('About searching');
    $this->assertText('About searching');
    $this->assertText('Use upper-case OR to get more results');

    // Search for a longer text, and see that it is in the title, truncated.
    $edit = [];
    $search_terms = 'Every word is like an unnecessary stain on silence and nothingness.';
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, 'Search');
    $this->assertSession()->titleEquals('Search for Every word is like an unnecessary stain on silence and… | Drupal');

    // Search for a string with a lot of special characters.
    $search_terms = 'Hear nothing > "see nothing" `feel' . " '1982.";
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, 'Search');
    $actual_title = $this->xpath('//title')[0]->getText();
    $this->assertEqual(Html::decodeEntities(t($title_source, ['@keywords' => Unicode::truncate($search_terms, 60, TRUE, TRUE)])), $actual_title, 'Search page title is correct');

    $edit['keys'] = $this->searchingUser->getAccountName();
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText('Search');
    $this->assertSession()->titleEquals('Search for ' . Unicode::truncate($this->searchingUser->getAccountName(), 60, TRUE, TRUE) . ' | Drupal');

    $this->clickLink('About searching');
    $this->assertText('About searching');
    $this->assertText('user names and partial user names');

    // Test that search keywords containing slashes are correctly loaded
    // from the GET params and displayed in the search form.
    $arg = $this->randomMachineName() . '/' . $this->randomMachineName();
    $this->drupalGet('search/node', ['query' => ['keys' => $arg]]);
    $this->assertSession()->elementExists('xpath', "//input[@id='edit-keys' and @value='{$arg}']");

    // Test a search input exceeding the limit of AND/OR combinations to test
    // the Denial-of-Service protection.
    $limit = $this->config('search.settings')->get('and_or_limit');
    $keys = [];
    for ($i = 0; $i < $limit + 1; $i++) {
      // Use a key of 4 characters to ensure we never generate 'AND' or 'OR'.
      $keys[] = $this->randomMachineName(4);
      if ($i % 2 == 0) {
        $keys[] = 'OR';
      }
    }
    $edit['keys'] = implode(' ', $keys);
    $this->drupalPostForm('search/node', $edit, 'Search');
    $this->assertRaw(t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', ['@count' => $limit]));

    // Test that a search on Node or User with no keywords entered generates
    // the "Please enter some keywords" message.
    $this->drupalPostForm('search/node', [], 'Search');
    $this->assertText('Please enter some keywords');
    $this->drupalPostForm('search/user', [], 'Search');
    $this->assertText('Please enter some keywords');

    // Make sure the "Please enter some keywords" message is NOT displayed if
    // you use "or" words or phrases in Advanced Search.
    $this->drupalPostForm('search/node', ['or' => $this->randomMachineName() . ' ' . $this->randomMachineName()], 'edit-submit--2');
    $this->assertNoText('Please enter some keywords');
    $this->drupalPostForm('search/node', ['phrase' => '"' . $this->randomMachineName() . '" "' . $this->randomMachineName() . '"'], 'edit-submit--2');
    $this->assertNoText('Please enter some keywords');

    // Verify that if you search for a too-short keyword, you get the right
    // message, and that if after that you search for a longer keyword, you
    // do not still see the message.
    $this->drupalPostForm('search/node', ['keys' => $this->randomMachineName(1)], 'Search');
    $this->assertText('You must include at least one keyword');
    $this->assertNoText('Please enter some keywords');
    $this->submitForm(['keys' => $this->randomMachineName()], 'Search');
    $this->assertNoText('You must include at least one keyword');

    // Test that if you search for a URL with .. in it, you still end up at
    // the search page. See issue https://www.drupal.org/node/890058.
    $this->drupalPostForm('search/node', ['keys' => '../../admin'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText('no results');

    // Test that if you search for a URL starting with "./", you still end up
    // at the search page. See issue https://www.drupal.org/node/1421560.
    $this->drupalPostForm('search/node', ['keys' => '.something'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText('no results');
  }

}
