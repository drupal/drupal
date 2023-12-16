<?php

namespace Drupal\Tests\system\Functional\Pager;

use Behat\Mink\Element\NodeElement;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests pager functionality.
 *
 * @group Pager
 */
class PagerTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dblog', 'image', 'pager_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to access site reports.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->adminUser = $this->drupalCreateUser([
      'access site reports',
      'administer image styles',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests markup and CSS classes of pager links.
   */
  public function testActiveClass() {
    // Verify first page.
    $this->drupalGet('admin/reports/dblog');
    $current_page = 0;
    $this->assertPagerItems($current_page);

    // Verify any page but first/last.
    $current_page++;
    $this->drupalGet('admin/reports/dblog', ['query' => ['page' => $current_page]]);
    $this->assertPagerItems($current_page);

    // Verify last page.
    $element = $this->assertSession()->elementExists('xpath', '//li[contains(@class, "pager__item--last")]/a');
    preg_match('@page=(\d+)@', $element->getAttribute('href'), $matches);
    $current_page = (int) $matches[1];
    $this->drupalGet($this->getAbsoluteUrl(parse_url($this->getUrl())['path'] . $element->getAttribute('href')), ['external' => TRUE]);
    $this->assertPagerItems($current_page);

    // Verify the pager does not render on a list without pagination.
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertSession()->elementNotExists('css', '.pager');
  }

  /**
   * Tests pager query parameters and cache context.
   */
  public function testPagerQueryParametersAndCacheContext() {
    // First page.
    $this->drupalGet('pager-test/query-parameters');
    $this->assertSession()->pageTextContains('Pager calls: 0');
    $this->assertSession()->pageTextContains('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');

    // Go to last page, the count of pager calls need to go to 1.
    $this->assertSession()->elementExists('xpath', '//li[contains(@class, "pager__item--last")]/a')->click();
    $this->assertSession()->pageTextContains('Pager calls: 1');
    $this->assertSession()->pageTextContains('[url.query_args.pagers:0]=0.60');
    $this->assertCacheContext('url.query_args');

    // Reset counter to 0.
    $this->drupalGet('pager-test/query-parameters');
    // Go back to first page, the count of pager calls need to go to 2.
    $this->assertSession()->elementExists('xpath', '//li[contains(@class, "pager__item--last")]/a')->click();
    $this->assertSession()->elementExists('xpath', '//li[contains(@class, "pager__item--first")]/a')->click();
    $this->assertSession()->pageTextContains('Pager calls: 2');
    $this->assertSession()->pageTextContains('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');
  }

  /**
   * Tests proper functioning of multiple pagers.
   */
  public function testMultiplePagers() {
    // First page.
    $this->drupalGet('pager-test/multiple-pagers');

    // Test data.
    // Expected URL query string param is 0-indexed.
    // Expected page per pager is 1-indexed.
    $test_data = [
      // With no query, all pagers set to first page.
      [
        'input_query' => '',
        'expected_page' => [0 => '1', 1 => '1', 4 => '1'],
        'expected_query' => '?page=0,0,,,0',
      ],
      // Blanks around page numbers should not be relevant.
      [
        'input_query' => '?page=2  ,    10,,,   5     ,,',
        'expected_page' => [0 => '3', 1 => '11', 4 => '6'],
        'expected_query' => '?page=2,10,,,5',
      ],
      // Blanks within page numbers should lead to only the first integer
      // to be considered.
      [
        'input_query' => '?page=2  ,   3 0,,,   4  13    ,,',
        'expected_page' => [0 => '3', 1 => '4', 4 => '5'],
        'expected_query' => '?page=2,3,,,4',
      ],
      // If floats are passed as page numbers, only the integer part is
      // returned.
      [
        'input_query' => '?page=2.1,6.999,,,5.',
        'expected_page' => [0 => '3', 1 => '7', 4 => '6'],
        'expected_query' => '?page=2,6,,,5',
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_query' => '?page=5,2',
        'expected_page' => [0 => '6', 1 => '3', 4 => '1'],
        'expected_query' => '?page=5,2,,,0',
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_query' => '?page=,2',
        'expected_page' => [0 => '1', 1 => '3', 4 => '1'],
        'expected_query' => '?page=0,2,,,0',
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_query' => '?page=,',
        'expected_page' => [0 => '1', 1 => '1', 4 => '1'],
        'expected_query' => '?page=0,0,,,0',
      ],
      // With overflow pages, all pagers set to max page.
      [
        'input_query' => '?page=99,99,,,99',
        'expected_page' => [0 => '16', 1 => '16', 4 => '16'],
        'expected_query' => '?page=15,15,,,15',
      ],
      // Wrong value for the page resets pager to first page.
      [
        'input_query' => '?page=bar,5,foo,qux,bet',
        'expected_page' => [0 => '1', 1 => '6', 4 => '1'],
        'expected_query' => '?page=0,5,,,0',
      ],
    ];

    // We loop through the page with the test data query parameters, and check
    // that the active page for each pager element has the expected page
    // (1-indexed) and resulting query parameter
    foreach ($test_data as $data) {
      $input_query = str_replace(' ', '%20', $data['input_query']);
      $this->drupalGet($this->getAbsoluteUrl(parse_url($this->getUrl())['path'] . $input_query), ['external' => TRUE]);
      foreach ([0, 1, 4] as $pager_element) {
        $active_page = $this->cssSelect("div.test-pager-{$pager_element} ul.pager__items li.is-active:contains('{$data['expected_page'][$pager_element]}')");
        $destination = str_replace('%2C', ',', $active_page[0]->find('css', 'a')->getAttribute('href'));
        $this->assertEquals($data['expected_query'], $destination);
      }
    }
  }

  /**
   * Tests proper functioning of the ellipsis.
   */
  public function testPagerEllipsis() {
    // Insert 100 extra log messages to get 9 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 100; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('…')");
    $this->assertCount(0, $elements, 'No ellipsis has been set.');

    // Insert an extra 50 log messages to get 10 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 50; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('…')");
    $this->assertCount(1, $elements, 'Found the ellipsis.');
  }

  /**
   * Asserts pager items and links.
   *
   * @param int $current_page
   *   The current pager page the internal browser is on.
   *
   * @internal
   */
  protected function assertPagerItems(int $current_page): void {
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertNotEmpty($elements, 'Pager found.');

    // Make current page 1-based.
    $current_page++;

    // Extract first/previous and next/last items.
    // first/previous only exist, if the current page is not the first.
    if ($current_page > 1) {
      $first = array_shift($elements);
      $previous = array_shift($elements);
    }
    // next/last always exist, unless the current page is the last.
    if ($current_page != count($elements)) {
      $last = array_pop($elements);
      $next = array_pop($elements);
    }

    // We remove elements from the $elements array in the following code, so
    // we store the total number of pages for verifying the "last" link.
    $total_pages = count($elements);

    // Verify items and links to pages.
    foreach ($elements as $page => $element) {
      // Make item/page index 1-based.
      $page++;

      if ($current_page == $page) {
        $this->assertClass($element, 'is-active', 'Element for current page has .is-active class.');
        $link = $element->find('css', 'a');
        $this->assertNotEmpty($link, 'Element for current page has link.');
        $destination = $link->getAttribute('href');
        // URL query string param is 0-indexed.
        $this->assertEquals('?page=' . ($page - 1), $destination);
      }
      else {
        $this->assertNoClass($element, 'is-active', "Element for page $page has no .is-active class.");
        $this->assertClass($element, 'pager__item', "Element for page $page has .pager__item class.");
        $link = $element->find('css', 'a');
        $this->assertNotEmpty($link, "Link to page $page found.");
        // Pager link has an attribute set in pager_test_preprocess_pager().
        $this->assertEquals('yes', $link->getAttribute('pager-test'));
        $destination = $link->getAttribute('href');
        $this->assertEquals('?page=' . ($page - 1), $destination);
      }
      unset($elements[--$page]);
    }
    // Verify that no other items remain untested.
    $this->assertEmpty($elements, 'All expected items found.');

    // Verify first/previous and next/last items and links.
    if (isset($first)) {
      $this->assertClass($first, 'pager__item--first', 'Element for first page has .pager__item--first class.');
      $link = $first->find('css', 'a');
      $this->assertNotEmpty($link, 'Link to first page found.');
      $this->assertNoClass($link, 'is-active', 'Link to first page is not active.');
      $this->assertEquals('first', $link->getAttribute('pager-test'));
      $destination = $link->getAttribute('href');
      $this->assertEquals('?page=0', $destination);
    }
    if (isset($previous)) {
      $this->assertClass($previous, 'pager__item--previous', 'Element for first page has .pager__item--previous class.');
      $link = $previous->find('css', 'a');
      $this->assertNotEmpty($link, 'Link to previous page found.');
      $this->assertNoClass($link, 'is-active', 'Link to previous page is not active.');
      $this->assertEquals('previous', $link->getAttribute('pager-test'));
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEquals('?page=' . ($current_page - 2), $destination);
    }
    if (isset($next)) {
      $this->assertClass($next, 'pager__item--next', 'Element for next page has .pager__item--next class.');
      $link = $next->find('css', 'a');
      $this->assertNotEmpty($link, 'Link to next page found.');
      $this->assertNoClass($link, 'is-active', 'Link to next page is not active.');
      $this->assertEquals('next', $link->getAttribute('pager-test'));
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEquals('?page=' . $current_page, $destination);
    }
    if (isset($last)) {
      $link = $last->find('css', 'a');
      $this->assertClass($last, 'pager__item--last', 'Element for last page has .pager__item--last class.');
      $this->assertNotEmpty($link, 'Link to last page found.');
      $this->assertNoClass($link, 'is-active', 'Link to last page is not active.');
      $this->assertEquals('last', $link->getAttribute('pager-test'));
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed.
      $this->assertEquals('?page=' . ($total_pages - 1), $destination);
    }
  }

  /**
   * Asserts that an element has a given class.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   *
   * @internal
   */
  protected function assertClass(NodeElement $element, string $class, string $message = NULL): void {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertTrue($element->hasClass($class), $message);
  }

  /**
   * Asserts that an element does not have a given class.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   *
   * @internal
   */
  protected function assertNoClass(NodeElement $element, string $class, string $message = NULL): void {
    if (!isset($message)) {
      $message = "Class .$class not found.";
    }
    $this->assertFalse($element->hasClass($class), $message);
  }

}
