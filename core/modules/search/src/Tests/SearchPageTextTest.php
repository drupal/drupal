<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchPageTextTest.
 */

namespace Drupal\search\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;

/**
 * Tests the bike shed text on no results page, and text on the search page.
 *
 * @group search
 */
class SearchPageTextTest extends SearchTestBase {
  protected $searching_user;

  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->searching_user = $this->drupalCreateUser(array('search content', 'access user profiles', 'use advanced search'));
  }

  /**
   * Tests the failed search text, and various other text on the search page.
   */
  function testSearchText() {
    $this->drupalLogin($this->searching_user);
    $this->drupalGet('search/node');
    $this->assertText(t('Enter your keywords'));
    $this->assertText(t('Search'));
    $this->assertTitle(t('Search') . ' | Drupal', 'Search page title is correct');

    $edit = array();
    $search_terms = 'bike shed ' . $this->randomMachineName();
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText(t('Consider loosening your query with OR. bike OR shed will often show more results than bike shed.'), 'Help text is displayed when search returns no results.');
    $this->assertText(t('Search'));
    $title_source = 'Search for @keywords | Drupal';
    $this->assertTitle(t($title_source, array('@keywords' => Unicode::truncate($search_terms, 60, TRUE, TRUE))), 'Search page title is correct');
    $this->assertNoText('Node', 'Erroneous tab and breadcrumb text is not present');
    $this->assertNoText(t('Node'), 'Erroneous translated tab and breadcrumb text is not present');
    $this->assertText(t('Content'), 'Tab and breadcrumb text is present');

    // Search for a longer text, and see that it is in the title, truncated.
    $edit = array();
    $search_terms = 'Every word is like an unnecessary stain on silence and nothingness.';
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertTitle(t($title_source, array('@keywords' => 'Every word is like an unnecessary stain on silence and…')), 'Search page title is correct');

    // Search for a string with a lot of special characters.
    $search_terms = 'Hear nothing > "see nothing" `feel' . " '1982.";
    $edit['keys'] = $search_terms;
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $actual_title = (string) current($this->xpath('//title'));
    $this->assertEqual($actual_title, String::decodeEntities(t($title_source, array('@keywords' => Unicode::truncate($search_terms, 60, TRUE, TRUE)))), 'Search page title is correct');

    $edit['keys'] = $this->searching_user->getUsername();
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText(t('Search'));
    $this->assertTitle(t($title_source, array('@keywords' => Unicode::truncate($this->searching_user->getUsername(), 60, TRUE, TRUE))));

    // Test that search keywords containing slashes are correctly loaded
    // from the GET params and displayed in the search form.
    $arg = $this->randomMachineName() . '/' . $this->randomMachineName();
    $this->drupalGet('search/node', array('query' => array('keys' => $arg)));
    $input = $this->xpath("//input[@id='edit-keys' and @value='{$arg}']");
    $this->assertFalse(empty($input), 'Search keys with a / are correctly set as the default value in the search box.');

    // Test a search input exceeding the limit of AND/OR combinations to test
    // the Denial-of-Service protection.
    $limit = $this->config('search.settings')->get('and_or_limit');
    $keys = array();
    for ($i = 0; $i < $limit + 1; $i++) {
      // Use a key of 4 characters to ensure we never generate 'AND' or 'OR'.
      $keys[] = $this->randomMachineName(4);
      if ($i % 2 == 0) {
        $keys[] = 'OR';
      }
    }
    $edit['keys'] = implode(' ', $keys);
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw(t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', array('@count' => $limit)));

    // Test that a search on Node or User with no keywords entered generates
    // the "Please enter some keywords" message.
    $this->drupalPostForm('search/node', array(), t('Search'));
    $this->assertText(t('Please enter some keywords'), 'With no keywords entered, message is displayed on node page');
    $this->drupalPostForm('search/user', array(), t('Search'));
    $this->assertText(t('Please enter some keywords'), 'With no keywords entered, message is displayed on user page');

    // Make sure the "Please enter some keywords" message is NOT displayed if
    // you use "or" words or phrases in Advanced Search.
    $this->drupalPostForm('search/node', array('or' => $this->randomMachineName() . ' ' . $this->randomMachineName()), t('Advanced search'));
    $this->assertNoText(t('Please enter some keywords'), 'With advanced OR keywords entered, no keywords message is not displayed on node page');
    $this->drupalPostForm('search/node', array('phrase' => '"' . $this->randomMachineName() . '" "' . $this->randomMachineName() . '"'), t('Advanced search'));
    $this->assertNoText(t('Please enter some keywords'), 'With advanced phrase entered, no keywords message is not displayed on node page');

    // Verify that if you search for a too-short keyword, you get the right
    // message, and that if after that you search for a longer keyword, you
    // do not still see the message.
    $this->drupalPostForm('search/node', array('keys' => $this->randomMachineName(1)), t('Search'));
    $this->assertText('You must include at least one positive keyword', 'Keyword message is displayed when searching for short word');
    $this->assertNoText(t('Please enter some keywords'), 'With short word entered, no keywords message is not displayed');
    $this->drupalPostForm(NULL, array('keys' => $this->randomMachineName()), t('Search'));
    $this->assertNoText('You must include at least one positive keyword', 'Keyword message is not displayed when searching for long word after short word search');

    // Test that if you search for a URL with .. in it, you still end up at
    // the search page. See issue https://drupal.org/node/890058.
    $this->drupalPostForm('search/node', array('keys' => '../../admin'), t('Search'));
    $this->assertResponse(200, 'Searching for ../../admin with non-admin user does not lead to a 403 error');
    $this->assertText('no results', 'Searching for ../../admin with non-admin user gives you a no search results page');

    // Test that if you search for a URL starting with "./", you still end up
    // at the search page. See issue https://drupal.org/node/1421560.
    $this->drupalPostForm('search/node', array('keys' => '.something'), t('Search'));
    $this->assertResponse(200, 'Searching for .something does not lead to a 403 error');
    $this->assertText('no results', 'Searching for .something gives you a no search results page');

  }
}
