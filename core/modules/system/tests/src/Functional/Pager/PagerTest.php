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
  public static $modules = ['dblog', 'pager_test'];

  /**
   * A user with permission to access site reports.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected $profile = 'testing';

  protected function setUp() {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->adminUser = $this->drupalCreateUser([
      'access site reports',
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
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--last']);
    preg_match('@page=(\d+)@', $elements[0]->getAttribute('href'), $matches);
    $current_page = (int) $matches[1];
    $this->drupalGet($GLOBALS['base_root'] . parse_url($this->getUrl())['path'] . $elements[0]->getAttribute('href'), ['external' => TRUE]);
    $this->assertPagerItems($current_page);
  }

  /**
   * Test proper functioning of the query parameters and the pager cache context.
   */
  public function testPagerQueryParametersAndCacheContext() {
    // First page.
    $this->drupalGet('pager-test/query-parameters');
    $this->assertText(t('Pager calls: 0'), 'Initial call to pager shows 0 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');

    // Go to last page, the count of pager calls need to go to 1.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--last']);
    $elements[0]->click();
    $this->assertText(t('Pager calls: 1'), 'First link call to pager shows 1 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.60');
    $this->assertCacheContext('url.query_args');

    // Reset counter to 0.
    $this->drupalGet('pager-test/query-parameters');
    // Go back to first page, the count of pager calls need to go to 2.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--last']);
    $elements[0]->click();
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--first']);
    $elements[0]->click();
    $this->assertText(t('Pager calls: 2'), 'Second link call to pager shows 2 calls.');
    $this->assertText('[url.query_args.pagers:0]=0.0');
    $this->assertCacheContext('url.query_args');
  }

  /**
   * Test proper functioning of multiple pagers.
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
        'input_query' => NULL,
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
      $this->drupalGet($GLOBALS['base_root'] . parse_url($this->getUrl())['path'] . $input_query, ['external' => TRUE]);
      foreach ([0, 1, 4] as $pager_element) {
        $active_page = $this->cssSelect("div.test-pager-{$pager_element} ul.pager__items li.is-active:contains('{$data['expected_page'][$pager_element]}')");
        $destination = str_replace('%2C', ',', $active_page[0]->find('css', 'a')->getAttribute('href'));
        $this->assertEqual($destination, $data['expected_query']);
      }
    }
  }

  /**
   * Test proper functioning of the ellipsis.
   */
  public function testPagerEllipsis() {
    // Insert 100 extra log messages to get 9 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 100; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('…')");
    $this->assertEqual(count($elements), 0, 'No ellipsis has been set.');

    // Insert an extra 50 log messages to get 10 pages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 50; $i++) {
      $logger->debug($this->randomString());
    }
    $this->drupalGet('admin/reports/dblog');
    $elements = $this->cssSelect(".pager__item--ellipsis:contains('…')");
    $this->assertEqual(count($elements), 1, 'Found the ellipsis.');
  }

  /**
   * Asserts pager items and links.
   *
   * @param int $current_page
   *   The current pager page the internal browser is on.
   */
  protected function assertPagerItems($current_page) {
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Pager found.');

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
        $this->assertTrue($link, 'Element for current page has link.');
        $destination = $link->getAttribute('href');
        // URL query string param is 0-indexed.
        $this->assertEqual($destination, '?page=' . ($page - 1));
      }
      else {
        $this->assertNoClass($element, 'is-active', "Element for page $page has no .is-active class.");
        $this->assertClass($element, 'pager__item', "Element for page $page has .pager__item class.");
        $link = $element->find('css', 'a');
        $this->assertTrue($link, "Link to page $page found.");
        $destination = $link->getAttribute('href');
        $this->assertEqual($destination, '?page=' . ($page - 1));
      }
      unset($elements[--$page]);
    }
    // Verify that no other items remain untested.
    $this->assertTrue(empty($elements), 'All expected items found.');

    // Verify first/previous and next/last items and links.
    if (isset($first)) {
      $this->assertClass($first, 'pager__item--first', 'Element for first page has .pager__item--first class.');
      $link = $first->find('css', 'a');
      $this->assertTrue($link, 'Link to first page found.');
      $this->assertNoClass($link, 'is-active', 'Link to first page is not active.');
      $destination = $link->getAttribute('href');
      $this->assertEqual($destination, '?page=0');
    }
    if (isset($previous)) {
      $this->assertClass($previous, 'pager__item--previous', 'Element for first page has .pager__item--previous class.');
      $link = $previous->find('css', 'a');
      $this->assertTrue($link, 'Link to previous page found.');
      $this->assertNoClass($link, 'is-active', 'Link to previous page is not active.');
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEqual($destination, '?page=' . ($current_page - 2));
    }
    if (isset($next)) {
      $this->assertClass($next, 'pager__item--next', 'Element for next page has .pager__item--next class.');
      $link = $next->find('css', 'a');
      $this->assertTrue($link, 'Link to next page found.');
      $this->assertNoClass($link, 'is-active', 'Link to next page is not active.');
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed, $current_page is 1-indexed.
      $this->assertEqual($destination, '?page=' . $current_page);
    }
    if (isset($last)) {
      $link = $last->find('css', 'a');
      $this->assertClass($last, 'pager__item--last', 'Element for last page has .pager__item--last class.');
      $this->assertTrue($link, 'Link to last page found.');
      $this->assertNoClass($link, 'is-active', 'Link to last page is not active.');
      $destination = $link->getAttribute('href');
      // URL query string param is 0-indexed.
      $this->assertEqual($destination, '?page=' . ($total_pages - 1));
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
   */
  protected function assertClass(NodeElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertTrue($element->hasClass($class) !== FALSE, $message);
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
   */
  protected function assertNoClass(NodeElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class not found.";
    }
    $this->assertTrue($element->hasClass($class) === FALSE, $message);
  }

}
