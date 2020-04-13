<?php

namespace Drupal\FunctionalJavascriptTests\Core\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for form grouping elements.
 *
 * @group form
 */
class FormGroupingElementsTest extends WebDriverTestBase {

  /**
   * Required modules.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
  }

  /**
   * Tests that vertical tab children become visible.
   *
   * Makes sure that a child element of a vertical tab that is not visible,
   * becomes visible when the tab is clicked, a fragment link to the child is
   * clicked or when the URI fragment pointing to that child changes.
   */
  public function testVerticalTabChildVisibility() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Request the group vertical tabs testing page with a fragment identifier
    // to the second element.
    $this->drupalGet('form-test/group-vertical-tabs', ['fragment' => 'edit-element-2']);

    $page = $session->getPage();

    $tab_link_1 = $page->find('css', '.vertical-tabs__menu-item > a');

    $child_1_selector = '#edit-element';
    $child_1 = $page->find('css', $child_1_selector);

    $child_2_selector = '#edit-element-2';
    $child_2 = $page->find('css', $child_2_selector);

    // Assert that the child in the second vertical tab becomes visible.
    // It should be visible after initial load due to the fragment in the URI.
    $this->assertTrue($child_2->isVisible(), 'Child 2 is visible due to a URI fragment');

    // Click on a fragment link pointing to an invisible child inside an
    // inactive vertical tab.
    $session->executeScript("jQuery('<a href=\"$child_1_selector\"></a>').insertAfter('h1')[0].click()");

    // Assert that the child in the first vertical tab becomes visible.
    $web_assert->waitForElementVisible('css', $child_1_selector, 50);

    // Trigger a URI fragment change (hashchange) to show the second vertical
    // tab again.
    $session->executeScript("location.replace('$child_2_selector')");

    // Assert that the child in the second vertical tab becomes visible again.
    $web_assert->waitForElementVisible('css', $child_2_selector, 50);

    $tab_link_1->click();

    // Assert that the child in the first vertical tab is visible again after
    // a click on the first tab.
    $this->assertTrue($child_1->isVisible(), 'Child 1 is visible after clicking the parent tab');
  }

  /**
   * Tests that details element children become visible.
   *
   * Makes sure that a child element of a details element that is not visible,
   * becomes visible when a fragment link to the child is clicked or when the
   * URI fragment pointing to that child changes.
   */
  public function testDetailsChildVisibility() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Store reusable JavaScript code to remove the current URI fragment and
    // close all details.
    $reset_js = "location.replace('#'); jQuery('details').removeAttr('open')";

    // Request the group details testing page.
    $this->drupalGet('form-test/group-details');

    $page = $session->getPage();

    $session->executeScript($reset_js);

    $child_selector = '#edit-element';
    $child = $page->find('css', $child_selector);

    // Assert that the child is not visible.
    $this->assertFalse($child->isVisible(), 'Child is not visible');

    // Trigger a URI fragment change (hashchange) to open all parent details
    // elements of the child.
    $session->executeScript("location.replace('$child_selector')");

    // Assert that the child becomes visible again after a hash change.
    $web_assert->waitForElementVisible('css', $child_selector, 50);

    $session->executeScript($reset_js);

    // Click on a fragment link pointing to an invisible child inside a closed
    // details element.
    $session->executeScript("jQuery('<a href=\"$child_selector\"></a>').insertAfter('h1')[0].click()");

    // Assert that the child is visible again after a fragment link click.
    $web_assert->waitForElementVisible('css', $child_selector, 50);

    // Find the summary belonging to the closest details element.
    $summary = $page->find('css', '#edit-meta > summary');

    // Assert that both aria-expanded and aria-pressed are true.
    $this->assertEquals('true', $summary->getAttribute('aria-expanded'));
    $this->assertEquals('true', $summary->getAttribute('aria-pressed'));
  }

}
