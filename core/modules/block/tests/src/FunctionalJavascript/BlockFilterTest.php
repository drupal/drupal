<?php

namespace Drupal\Tests\block\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the block add filter.
 *
 * @group block
 */
class BlockFilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);

    $this->drupalLogin($admin_user);
  }

  /**
   * Tests block filter.
   */
  public function testBlockFilter() {
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Find the block filter field on the add-block dialog.
    $page->find('css', '#edit-blocks-region-header-title')->click();
    $filter = $assertSession->waitForElement('css', '.block-filter-text');

    // Get all block rows, for assertions later.
    $block_rows = $page->findAll('css', '.block-add-table tbody tr');

    // Test block filter reduces the number of visible rows.
    $filter->setValue('ad');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("blocks are available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    if (count($block_rows) > 0) {
      $this->assertNotSameSize($block_rows, $visible_rows);
    }

    // Test Drupal.announce() message when multiple matches are expected.
    $expected_message = count($visible_rows) . ' blocks are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test Drupal.announce() message when only one match is expected.
    $filter->setValue('Powered by');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("block is available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    $this->assertCount(1, $visible_rows);
    $expected_message = '1 block is available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test Drupal.announce() message when no matches are expected.
    $filter->setValue('Pan-Galactic Gargle Blaster');
    $session->wait(10000, 'jQuery("#drupal-live-announce").html().indexOf("0 blocks are available") > -1');
    $visible_rows = $this->filterVisibleElements($block_rows);
    $this->assertCount(0, $visible_rows);
    $expected_message = '0 blocks are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   An array of node elements.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   */
  protected function filterVisibleElements(array $elements) {
    $elements = array_filter($elements, function (NodeElement $element) {
      return $element->isVisible();
    });
    return $elements;
  }

}
