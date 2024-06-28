<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

// cspell:ignore fieldbody fieldlinks

/**
 * Tests moving blocks via the form.
 *
 * @group layout_builder
 */
class MoveBlockFormTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
    $this->createNode([
      'type' => 'bundle_with_section_field',
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access contextual links',
    ]));

    $this->drupalGet('node/1/layout');
    $expected_block_order = [
      '.block-extra-field-blocknodebundle-with-section-fieldlinks',
      '.block-field-blocknodebundle-with-section-fieldbody',
    ];
    $this->markTestSkipped("Skipped temporarily for random fails.");
    $this->assertRegionBlocksOrder(0, 'content', $expected_block_order);

    // Add a top section using the Two column layout.
    $page->clickLink('Add section');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Two column');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add section"]'));
    $page->pressButton('Add section');
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order);

    // Add a 'Powered by Drupal' block in the 'first' region of the new section.
    $first_region_block_locator = '[data-layout-delta="0"].layout--twocol-section [data-region="first"] [data-layout-block-uuid]';
    $assert_session->elementNotExists('css', $first_region_block_locator);
    $assert_session->elementExists('css', '[data-layout-delta="0"].layout--twocol-section [data-region="first"] .layout-builder__add-block')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas a:contains("Powered by Drupal")'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Powered by Drupal');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add block"]'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $first_region_block_locator));

    // Ensure the request has completed before the test starts.
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Tests moving a block.
   */
  public function testMoveBlock(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Reorder body field in current region.
    $this->openBodyMoveForm(1, 'content', ['Links', 'Body (current)']);
    $this->moveBlockWithKeyboard('up', 'Body (current)', ['Body (current)*', 'Links']);
    $page->pressButton('Move');
    $expected_block_order = [
      '.block-field-blocknodebundle-with-section-fieldbody',
      '.block-extra-field-blocknodebundle-with-section-fieldlinks',
    ];
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order);
    $page->pressButton('Save layout');
    $page->clickLink('Layout');
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order);

    // Move the body block into the first region above existing block.
    $this->openBodyMoveForm(1, 'content', ['Body (current)', 'Links']);
    $page->selectFieldOption('Region', '0:first');
    $this->markTestSkipped("Skipped temporarily for random fails.");
    $this->assertBlockTable(['Powered by Drupal', 'Body (current)']);
    $this->moveBlockWithKeyboard('up', 'Body', ['Body (current)*', 'Powered by Drupal']);
    $page->pressButton('Move');
    $expected_block_order = [
      '.block-field-blocknodebundle-with-section-fieldbody',
      '.block-system-powered-by-block',
    ];
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);

    // Ensure the body block is no longer in the content region.
    $this->assertRegionBlocksOrder(1, 'content', ['.block-extra-field-blocknodebundle-with-section-fieldlinks']);
    $page->pressButton('Save layout');
    $page->clickLink('Layout');
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);

    // Move into the second region that has no existing blocks.
    $this->openBodyMoveForm(0, 'first', ['Body (current)', 'Powered by Drupal']);
    $page->selectFieldOption('Region', '0:second');
    $this->assertBlockTable(['Body (current)']);
    $page->pressButton('Move');
    $this->assertRegionBlocksOrder(0, 'second', ['.block-field-blocknodebundle-with-section-fieldbody']);

    // The weight element uses -10 to 10 by default, which can cause bugs.
    // Add 25 'Powered by Drupal' blocks to a new section.
    $page->clickLink('Add section');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('One column');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add section"]'));
    $page->pressButton('Add section');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $large_block_number = 25;
    for ($i = 0; $i < $large_block_number; $i++) {
      $assert_session->elementExists('css', '[data-layout-delta="0"].layout--onecol [data-region="content"] .layout-builder__add-block')->click();
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas a:contains("Powered by Drupal")'));
      $assert_session->assertWaitOnAjaxRequest();
      $page->clickLink('Powered by Drupal');
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add block"]'));
      $assert_session->assertWaitOnAjaxRequest();
      $page->pressButton('Add block');
      $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    }
    $first_region_block_locator = '[data-layout-delta="0"].layout--onecol [data-region="content"] [data-layout-block-uuid]';
    $assert_session->elementsCount('css', $first_region_block_locator, $large_block_number);

    // Move the Body block to the end of the new section.
    $this->openBodyMoveForm(1, 'second', ['Body (current)']);
    $page->selectFieldOption('Region', '0:content');
    $expected_block_table = array_fill(0, $large_block_number, 'Powered by Drupal');
    $expected_block_table[] = 'Body (current)';
    $this->assertBlockTable($expected_block_table);
    $expected_block_table = array_fill(0, $large_block_number - 1, 'Powered by Drupal');
    $expected_block_table[] = 'Body (current)*';
    $expected_block_table[] = 'Powered by Drupal';
    $this->moveBlockWithKeyboard('up', 'Body', $expected_block_table);
    $page->pressButton('Move');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    // Get all blocks currently in the region.
    $blocks = $page->findAll('css', $first_region_block_locator);
    // The second to last $block should be the body.
    $this->assertTrue($blocks[count($blocks) - 2]->hasClass('block-field-blocknodebundle-with-section-fieldbody'));
  }

  /**
   * Asserts the correct block labels appear in the draggable tables.
   *
   * @param string[] $expected_block_labels
   *   The expected block labels.
   *
   * @internal
   */
  protected function assertBlockTable(array $expected_block_labels): void {
    $page = $this->getSession()->getPage();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $block_tds = $page->findAll('css', '.layout-builder-components-table__block-label');
    $this->assertSameSize($block_tds, $expected_block_labels);
    /** @var \Behat\Mink\Element\NodeElement $block_td */
    foreach ($block_tds as $block_td) {
      $this->assertSame(array_shift($expected_block_labels), trim($block_td->getText()));
    }
  }

  /**
   * Moves a block in the draggable table.
   *
   * @param string $direction
   *   The direction to move the block in the table.
   * @param string $block_label
   *   The block label.
   * @param array $updated_blocks
   *   The updated blocks order.
   */
  protected function moveBlockWithKeyboard($direction, $block_label, array $updated_blocks) {
    $keys = [
      'up' => 38,
      'down' => 40,
    ];
    $key = $keys[$direction];
    $handle = $this->findRowHandle($block_label);

    $handle->keyDown($key);
    $handle->keyUp($key);

    $handle->blur();
    $this->assertBlockTable($updated_blocks);
  }

  /**
   * Finds the row handle for a block in the draggable table.
   *
   * @param string $block_label
   *   The block label.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row handle element.
   */
  protected function findRowHandle($block_label) {
    $assert_session = $this->assertSession();
    return $assert_session->elementExists('css', "[data-drupal-selector=\"edit-components\"] td:contains(\"$block_label\") a.tabledrag-handle");
  }

  /**
   * Asserts that blocks are in the correct order for a region.
   *
   * @param int $section_delta
   *   The section delta.
   * @param string $region
   *   The region.
   * @param array $expected_block_selectors
   *   The block selectors.
   *
   * @internal
   */
  protected function assertRegionBlocksOrder(int $section_delta, string $region, array $expected_block_selectors): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $region_selector = "[data-layout-delta=\"$section_delta\"] [data-region=\"$region\"]";

    // Get all blocks currently in the region.
    $blocks = $page->findAll('css', "$region_selector [data-layout-block-uuid]");
    $this->assertSameSize($expected_block_selectors, $blocks);

    /** @var \Behat\Mink\Element\NodeElement $block */
    foreach ($blocks as $block) {
      $block_selector = array_shift($expected_block_selectors);
      $assert_session->elementsCount('css', "$region_selector $block_selector", 1);
      $expected_block = $page->find('css', "$region_selector $block_selector");
      $this->assertSame($expected_block->getAttribute('data-layout-block-uuid'), $block->getAttribute('data-layout-block-uuid'));
    }
  }

  /**
   * Open block for the body field.
   *
   * @param int $delta
   *   The section delta where the field should be.
   * @param string $region
   *   The region where the field should be.
   * @param array $initial_blocks
   *   The initial blocks that should be shown in the draggable table.
   */
  protected function openBodyMoveForm($delta, $region, array $initial_blocks) {
    $assert_session = $this->assertSession();

    $body_field_locator = "[data-layout-delta=\"$delta\"] [data-region=\"$region\"] .block-field-blocknodebundle-with-section-fieldbody";
    $this->clickContextualLink($body_field_locator, 'Move');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('named', ['select', 'Region']));
    $assert_session->fieldValueEquals('Region', "$delta:$region");
    $this->assertBlockTable($initial_blocks);
  }

}
