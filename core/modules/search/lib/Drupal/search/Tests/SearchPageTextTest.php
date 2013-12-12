<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchPageTextTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests the bike shed text on no results page, and text on the search page.
 */
class SearchPageTextTest extends SearchTestBase {
  protected $searching_user;

  public static function getInfo() {
    return array(
      'name' => 'Search page text',
      'description' => 'Tests the bike shed text on the no results page, and various other text on search pages.',
      'group' => 'Search'
    );
  }

  function setUp() {
    parent::setUp();

    // Create user.
    $this->searching_user = $this->drupalCreateUser(array('search content', 'access user profiles'));
  }

  /**
   * Tests the failed search text, and various other text on the search page.
   */
  function testSearchText() {
    $this->drupalLogin($this->searching_user);
    $this->drupalGet('search/node');
    $this->assertText(t('Enter your keywords'));
    $this->assertText(t('Search'));
    $title = t('Search') . ' | Drupal';
    $this->assertTitle($title, 'Search page title is correct');

    $edit = array();
    $edit['keys'] = 'bike shed ' . $this->randomName();
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText(t('Consider loosening your query with OR. bike OR shed will often show more results than bike shed.'), 'Help text is displayed when search returns no results.');
    $this->assertText(t('Search'));
    $this->assertTitle($title, 'Search page title is correct');
    $this->assertNoText('Node', 'Erroneous tab and breadcrumb text is not present');
    $this->assertNoText(t('Node'), 'Erroneous translated tab and breadcrumb text is not present');
    $this->assertText(t('Content'), 'Tab and breadcrumb text is present');

    $edit['keys'] = $this->searching_user->getUsername();
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText(t('Search'));
    $this->assertTitle($title, 'Search page title is correct');

    // Test that search keywords containing slashes are correctly loaded
    // from the path and displayed in the search form.
    $arg = $this->randomName() . '/' . $this->randomName();
    $this->drupalGet('search/node/' . $arg);
    $input = $this->xpath("//input[@id='edit-keys' and @value='{$arg}']");
    $this->assertFalse(empty($input), 'Search keys with a / are correctly set as the default value in the search box.');

    // Test a search input exceeding the limit of AND/OR combinations to test
    // the Denial-of-Service protection.
    $limit = \Drupal::config('search.settings')->get('and_or_limit');
    $keys = array();
    for ($i = 0; $i < $limit + 1; $i++) {
      $keys[] = $this->randomName(3);
      if ($i % 2 == 0) {
        $keys[] = 'OR';
      }
    }
    $edit['keys'] = implode(' ', $keys);
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertRaw(t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', array('@count' => $limit)));
  }
}
