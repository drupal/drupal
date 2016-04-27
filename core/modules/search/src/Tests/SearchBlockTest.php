<?php

namespace Drupal\search\Tests;

/**
 * Tests if the search form block is available.
 *
 * @group search
 */
class SearchBlockTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'search content'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the search form block can be placed and works.
   */
  public function testSearchFormBlock() {

    // Test availability of the search block in the admin "Place blocks" list.
    $this->drupalGet('admin/structure/block');
    $this->clickLinkPartialName('Place block');
    $this->assertLinkByHref('/admin/structure/block/add/search_form_block/classy', 0,
      'Did not find the search block in block candidate list.');

    $block = $this->drupalPlaceBlock('search_form_block');

    $this->drupalGet('');
    $this->assertText($block->label(), 'Block title was found.');

    // Check that name attribute is not empty.
    $pattern = "//input[@type='submit' and @name='']";
    $elements = $this->xpath($pattern);
    $this->assertTrue(empty($elements), 'The search input field does not have empty name attribute.');

    // Test a normal search via the block form, from the front page.
    $terms = array('keys' => 'test');
    $this->submitGetForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    // Test a search from the block on a 404 page.
    $this->drupalGet('foo');
    $this->assertResponse(404);
    $this->submitGetForm(NULL, $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    $visibility = $block->getVisibility();
    $visibility['request_path']['pages'] = 'search';
    $block->setVisibilityConfig('request_path', $visibility['request_path']);

    $this->submitGetForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    // Confirm that the form submits to the default search page.
    /** @var $search_page_repository \Drupal\search\SearchPageRepositoryInterface */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $entity_id = $search_page_repository->getDefaultSearchPage();
    $this->assertEqual(
      $this->getUrl(),
      \Drupal::url('search.view_' . $entity_id, array(), array('query' => array('keys' => $terms['keys']), 'absolute' => TRUE)),
      'Submitted to correct URL.'
    );

    // Test an empty search via the block form, from the front page.
    $terms = array('keys' => '');
    $this->submitGetForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Please enter some keywords');

    // Confirm that the user is redirected to the search page, when form is
    // submitted empty.
    $this->assertEqual(
      $this->getUrl(),
      \Drupal::url('search.view_' . $entity_id, array(), array('query' => array('keys' => ''), 'absolute' => TRUE)),
      'Redirected to correct URL.'
    );

    // Test that after entering a too-short keyword in the form, you can then
    // search again with a longer keyword. First test using the block form.
    $this->submitGetForm('node', array('keys' => $this->randomMachineName(1)), t('Search'));
    $this->assertText('You must include at least one keyword to match in the content', 'Keyword message is displayed when searching for short word');
    $this->assertNoText(t('Please enter some keywords'), 'With short word entered, no keywords message is not displayed');
    $this->submitGetForm(NULL, array('keys' => $this->randomMachineName()), t('Search'), 'search-block-form');
    $this->assertNoText('You must include at least one keyword to match in the content', 'Keyword message is not displayed when searching for long word after short word search');

    // Same test again, using the search page form for the second search this
    // time.
    $this->submitGetForm('node', array('keys' => $this->randomMachineName(1)), t('Search'));
    $this->drupalPostForm(NULL, array('keys' => $this->randomMachineName()), t('Search'), array(), array(), 'search-form');
    $this->assertNoText('You must include at least one keyword to match in the content', 'Keyword message is not displayed when searching for long word after short word search');

  }

}
