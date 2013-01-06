<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchBlockTest.
 */

namespace Drupal\search\Tests;

class SearchBlockTest extends SearchTestBase {

  protected $adminUser;

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
    $this->adminUser = $this->drupalCreateUser(array('administer blocks', 'search content'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that the search form block can be placed and works.
   */
  protected function testSearchFormBlock() {
    $block_id = 'search_form_block';
    $default_theme = variable_get('theme_default', 'stark');

    $block = array(
      'title' => $this->randomName(8),
      'machine_name' => $this->randomName(8),
      'region' => 'content',
    );

    // Enable the search block.
    $this->drupalPost('admin/structure/block/manage/' . $block_id . '/' . $default_theme, $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), '"Search form" block enabled');
    $this->assertText($block['title'], 'Block title was found.');

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

    $block['config_id'] = 'plugin.core.block.' . $default_theme . '.' . $block['machine_name'];
    $config = config($block['config_id']);
    $config->set('visibility.path.pages', 'search');
    $config->save();

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
