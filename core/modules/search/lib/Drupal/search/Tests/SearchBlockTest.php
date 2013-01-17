<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchBlockTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests the rendering of the search block.
 */
class SearchBlockTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Check if the search form block is available.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'search content'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the search form block can be placed and works.
   */
  protected function testSearchFormBlock() {
    $block = $this->drupalPlaceBlock('search_form_block');

    $this->drupalGet('');
    $this->assertText($block->label(), 'Block title was found.');

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

    $visibility = $block->get('visibility');
    $visibility['path']['pages'] = 'search';
    $block->set('visibility', $visibility);

    $this->drupalPost('node', $terms, t('Search'));
    $this->assertText('Your search yielded no results');

    // Confirm that the user is redirected to the search page.
    $this->assertEqual(
      $this->getUrl(),
      url('search/node/' . $terms['search_block_form'], array('absolute' => TRUE)),
      'Redirected to correct url.'
    );

    // Test an empty search via the block form, from the front page.
    $terms = array('search_block_form' => '');
    $this->drupalPost('node', $terms, t('Search'));
    $this->assertText('Please enter some keywords');

    // Confirm that the user is redirected to the search page, when form is
    // submitted empty.
    $this->assertEqual(
      $this->getUrl(),
      url('search/node/', array('absolute' => TRUE)),
      'Redirected to correct url.'
    );
  }

}
