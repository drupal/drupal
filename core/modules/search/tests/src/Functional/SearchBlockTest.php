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
  protected $defaultTheme = 'stark';

  /**
   * The administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   * Tests that the search form block can be placed and works.
   */
  public function testSearchFormBlock() {

    // Test availability of the search block in the admin "Place blocks" list.
    $this->drupalGet('admin/structure/block');
    $this->getSession()->getPage()->findLink('Place block')->click();
    $this->assertSession()->linkByHrefExists('/admin/structure/block/add/search_form_block/stark', 0,
      'Did not find the search block in block candidate list.');

    $block = $this->drupalPlaceBlock('search_form_block');

    $this->drupalGet('');
    $this->assertSession()->pageTextContains($block->label());

    // Check that name attribute is not empty.
    $this->assertSession()->elementNotExists('xpath', "//input[@type='submit' and @name='']");

    // Test a normal search via the block form, from the front page.
    $terms = ['keys' => 'test'];
    $this->drupalGet('');
    $this->submitForm($terms, 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your search yielded no results');

    // Test a search from the block on a 404 page.
    $this->drupalGet('foo');
    $this->assertSession()->statusCodeEquals(404);
    $this->submitForm($terms, 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your search yielded no results');

    $visibility = $block->getVisibility();
    $visibility['request_path']['pages'] = 'search';
    $block->setVisibilityConfig('request_path', $visibility['request_path']);

    $this->drupalGet('');
    $this->submitForm($terms, 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your search yielded no results');

    // Confirm that the form submits to the default search page.
    /** @var \Drupal\search\SearchPageRepositoryInterface $search_page_repository */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $entity_id = $search_page_repository->getDefaultSearchPage();
    $this->assertEquals(
      $this->getUrl(),
      Url::fromRoute('search.view_' . $entity_id, [], ['query' => ['keys' => $terms['keys']], 'absolute' => TRUE])->toString(),
      'Submitted to correct URL.'
    );

    // Test an empty search via the block form, from the front page.
    $terms = ['keys' => ''];
    $this->drupalGet('');
    $this->submitForm($terms, 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->statusMessageContains('Please enter some keywords', 'error');

    // Confirm that the user is redirected to the search page, when form is
    // submitted empty.
    $this->assertEquals(
      $this->getUrl(),
      Url::fromRoute('search.view_' . $entity_id, [], ['query' => ['keys' => ''], 'absolute' => TRUE])->toString(),
      'Redirected to correct URL.'
    );

    // Test that after entering a too-short keyword in the form, you can then
    // search again with a longer keyword. First test using the block form.
    $this->drupalGet('node');
    $this->submitForm(['keys' => $this->randomMachineName(1)], 'Search');
    $this->assertSession()->statusMessageContains('You must include at least one keyword to match in the content', 'warning');
    $this->assertSession()->statusMessageNotContains('Please enter some keywords');
    $this->submitForm(['keys' => $this->randomMachineName()], 'Search', 'search-block-form');
    $this->assertSession()->statusMessageNotContains('You must include at least one keyword to match in the content');

    // Same test again, using the search page form for the second search this
    // time.
    $this->drupalGet('node');
    $this->submitForm(['keys' => $this->randomMachineName(1)], 'Search');
    $this->submitForm(['keys' => $this->randomMachineName()], 'Search', 'search-form');
    $this->assertSession()->statusMessageNotContains('You must include at least one keyword to match in the content');

    // Edit the block configuration so that it searches users instead of nodes,
    // and test.
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $this->submitForm(['settings[page_id]' => 'user_search'], 'Save block');

    $name = $this->adminUser->getAccountName();
    $this->drupalGet('node');
    $this->submitForm(['keys' => $name], 'Search');
    $this->assertSession()->linkExists($name);
  }

}
