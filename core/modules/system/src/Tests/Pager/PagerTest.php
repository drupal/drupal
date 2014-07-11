<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Pager\PagerTest.
 */

namespace Drupal\system\Tests\Pager;

use Drupal\simpletest\WebTestBase;

/**
 * Tests pager functionality.
 *
 * @group Pager
 */
class PagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog');

  protected $profile = 'testing';

  function setUp() {
    parent::setUp();

    // Insert 300 log messages.
    for ($i = 0; $i < 300; $i++) {
      watchdog('pager_test', $this->randomString(), array(), WATCHDOG_DEBUG);
    }

    $this->admin_user = $this->drupalCreateUser(array(
      'access site reports',
    ));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests markup and CSS classes of pager links.
   */
  function testActiveClass() {
    // Verify first page.
    $this->drupalGet('admin/reports/dblog');
    $current_page = 0;
    $this->assertPagerItems($current_page);

    // Verify any page but first/last.
    $current_page++;
    $this->drupalGet('admin/reports/dblog', array('query' => array('page' => $current_page)));
    $this->assertPagerItems($current_page);

    // Verify last page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager-last'));
    preg_match('@page=(\d+)@', $elements[0]['href'], $matches);
    $current_page = (int) $matches[1];
    $this->drupalGet($GLOBALS['base_root'] . $elements[0]['href'], array('external' => TRUE));
    $this->assertPagerItems($current_page);
  }

  /**
   * Asserts pager items and links.
   *
   * @param int $current_page
   *   The current pager page the internal browser is on.
   */
  protected function assertPagerItems($current_page) {
    $elements = $this->xpath('//ul[@class=:class]/li', array(':class' => 'pager'));
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

    // Verify items and links to pages.
    foreach ($elements as $page => $element) {
      // Make item/page index 1-based.
      $page++;
      if ($current_page == $page) {
        $this->assertClass($element, 'pager-current', 'Item for current page has .pager-current class.');
        $this->assertFalse(isset($element->a), 'Item for current page has no link.');
      }
      else {
        $this->assertNoClass($element, 'pager-current', "Item for page $page has no .pager-current class.");
        $this->assertClass($element, 'pager-item', "Item for page $page has .pager-item class.");
        $this->assertTrue($element->a, "Link to page $page found.");
        $this->assertNoClass($element->a, 'active', "Link to page $page is not active.");
      }
      unset($elements[--$page]);
    }
    // Verify that no other items remain untested.
    $this->assertTrue(empty($elements), 'All expected items found.');

    // Verify first/previous and next/last items and links.
    if (isset($first)) {
      $this->assertClass($first, 'pager-first', 'Item for first page has .pager-first class.');
      $this->assertTrue($first->a, 'Link to first page found.');
      $this->assertNoClass($first->a, 'active', 'Link to first page is not active.');
    }
    if (isset($previous)) {
      $this->assertClass($previous, 'pager-previous', 'Item for first page has .pager-previous class.');
      $this->assertTrue($previous->a, 'Link to previous page found.');
      $this->assertNoClass($previous->a, 'active', 'Link to previous page is not active.');
    }
    if (isset($next)) {
      $this->assertClass($next, 'pager-next', 'Item for next page has .pager-next class.');
      $this->assertTrue($next->a, 'Link to next page found.');
      $this->assertNoClass($next->a, 'active', 'Link to next page is not active.');
    }
    if (isset($last)) {
      $this->assertClass($last, 'pager-last', 'Item for last page has .pager-last class.');
      $this->assertTrue($last->a, 'Link to last page found.');
      $this->assertNoClass($last->a, 'active', 'Link to last page is not active.');
    }
  }

  /**
   * Asserts that an element has a given class.
   *
   * @param \SimpleXMLElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertClass(\SimpleXMLElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertTrue(strpos($element['class'], $class) !== FALSE, $message);
  }

  /**
   * Asserts that an element does not have a given class.
   *
   * @param \SimpleXMLElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertNoClass(\SimpleXMLElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class not found.";
    }
    $this->assertTrue(strpos($element['class'], $class) === FALSE, $message);
  }
}
