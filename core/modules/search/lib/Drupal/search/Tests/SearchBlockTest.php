<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchBlockTest.
 */

namespace Drupal\search\Tests;

class SearchBlockTest extends SearchTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Check if the search form block is available.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp(array('block'));

    // Create and login user
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'search content'));
    $this->drupalLogin($admin_user);
  }

  function testSearchFormBlock() {
    // Set block title to confirm that the interface is available.
    $this->drupalPost('admin/structure/block/manage/search/form/configure', array('title' => $this->randomName(8)), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), t('Block configuration set.'));

    // Set the block to a region to confirm block is available.
    $edit = array();
    $edit['blocks[search_form][region]'] = 'footer';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), t('Block successfully move to footer region.'));
  }

  /**
   * Test that the search block form works correctly.
   */
  function testBlock() {
    // Enable the block, and place it in the 'content' region so that it isn't
    // hidden on 404 pages.
    $edit = array('blocks[search_form][region]' => 'content');
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Test a normal search via the block form, from the front page.
    $terms = array('search_block_form' => 'test');
    $this->drupalPost('node', $terms, t('Search'));
    $this->assertText('Your search yielded no results');

    // Test a search from the block on a 404 page.
    $this->drupalGet('foo');
    $this->assertResponse(404);
    $this->drupalPost(NULL, $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    // Test a search from the block when it doesn't appear on the search page.
    $edit = array('pages' => 'search');
    $this->drupalPost('admin/structure/block/manage/search/form/configure', $edit, t('Save block'));
    $this->drupalPost('node', $terms, t('Search'));
    $this->assertText('Your search yielded no results');

    // Confirm that the user is redirected to the search page.
    $this->assertEqual(
      $this->getUrl(),
      url('search/node/' . $terms['search_block_form'], array('absolute' => TRUE)),
      t('Redirected to correct url.')
    );

    // Test an empty search via the block form, from the front page.
    $terms = array('search_block_form' => '');
    $this->drupalPost('node', $terms, t('Search'));
    $this->assertText('Please enter some keywords');

    // Confirm that the user is redirected to the search page, when form is submitted empty.
    $this->assertEqual(
      $this->getUrl(),
      url('search/node/', array('absolute' => TRUE)),
      t('Redirected to correct url.')
    );
  }
}
