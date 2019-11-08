<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if the search form block is available.
 *
 * @group search
 */
class SearchBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'search', 'dblog', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * The administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'search content',
      'access user profiles',
      'access content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that the search form block can be placed and works.
   */
  public function testSearchFormBlock() {

    // Test availability of the search block in the admin "Place blocks" list.
    $this->drupalGet('admin/structure/block');
    $this->getSession()->getPage()->findLink('Place block')->click();
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
    $terms = ['keys' => 'test'];
    $this->drupalPostForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    // Test a search from the block on a 404 page.
    $this->drupalGet('foo');
    $this->assertResponse(404);
    $this->drupalPostForm(NULL, $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    $visibility = $block->getVisibility();
    $visibility['request_path']['pages'] = 'search';
    $block->setVisibilityConfig('request_path', $visibility['request_path']);

    $this->drupalPostForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Your search yielded no results');

    // Confirm that the form submits to the default search page.
    /** @var $search_page_repository \Drupal\search\SearchPageRepositoryInterface */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $entity_id = $search_page_repository->getDefaultSearchPage();
    $this->assertEqual(
      $this->getUrl(),
      Url::fromRoute('search.view_' . $entity_id, [], ['query' => ['keys' => $terms['keys']], 'absolute' => TRUE])->toString(),
      'Submitted to correct URL.'
    );

    // Test an empty search via the block form, from the front page.
    $terms = ['keys' => ''];
    $this->drupalPostForm('', $terms, t('Search'));
    $this->assertResponse(200);
    $this->assertText('Please enter some keywords');

    // Confirm that the user is redirected to the search page, when form is
    // submitted empty.
    $this->assertEqual(
      $this->getUrl(),
      Url::fromRoute('search.view_' . $entity_id, [], ['query' => ['keys' => ''], 'absolute' => TRUE])->toString(),
      'Redirected to correct URL.'
    );

    // Test that after entering a too-short keyword in the form, you can then
    // search again with a longer keyword. First test using the block form.
    $this->drupalPostForm('node', ['keys' => $this->randomMachineName(1)], t('Search'));
    $this->assertText('You must include at least one keyword to match in the content', 'Keyword message is displayed when searching for short word');
    $this->assertNoText(t('Please enter some keywords'), 'With short word entered, no keywords message is not displayed');
    $this->drupalPostForm(NULL, ['keys' => $this->randomMachineName()], t('Search'), [], 'search-block-form');
    $this->assertNoText('You must include at least one keyword to match in the content', 'Keyword message is not displayed when searching for long word after short word search');

    // Same test again, using the search page form for the second search this
    // time.
    $this->drupalPostForm('node', ['keys' => $this->randomMachineName(1)], t('Search'));
    $this->drupalPostForm(NULL, ['keys' => $this->randomMachineName()], t('Search'), [], 'search-form');
    $this->assertNoText('You must include at least one keyword to match in the content', 'Keyword message is not displayed when searching for long word after short word search');

    // Edit the block configuration so that it searches users instead of nodes,
    // and test.
    $this->drupalPostForm('admin/structure/block/manage/' . $block->id(),
      [
        'settings[page_id]' => 'user_search',
      ], 'Save block');
    $name = $this->adminUser->getAccountName();
    $email = $this->adminUser->getEmail();
    $this->drupalPostForm('node', ['keys' => $name], t('Search'));
    $this->assertLink($name);
  }

}
