<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\system\Traits\OffCanvasTestTrait;

/**
 * Tests toggling of content preview.
 *
 * @group layout_builder
 */
class ContentPreviewToggleTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;
  use LayoutBuilderSortTrait;
  use OffCanvasTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
    'contextual',
    'off_canvas_test',
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

    $this->createContentType(['type' => 'bundle_for_this_particular_test']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_for_this_particular_test.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access contextual links',
    ]));
  }

  /**
   * Tests the content preview toggle.
   */
  public function testContentPreviewToggle() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $links_field_placeholder_label = '"Links" field';
    $body_field_placeholder_label = '"Body" field';
    $content_preview_body_text = 'I should only be visible if content preview is enabled.';

    $this->createNode([
      'type' => 'bundle_for_this_particular_test',
      'body' => [
        [
          'value' => $content_preview_body_text,
        ],
      ],
    ]);

    // Open single item layout page.
    $this->drupalGet('node/1/layout');

    // Placeholder label should not be visible, preview content should be.
    $assert_session->elementNotExists('css', '.layout-builder-block__content-preview-placeholder-label');
    $assert_session->pageTextContains($content_preview_body_text);

    // Disable content preview, confirm presence of placeholder labels.
    $this->assertTrue($page->hasCheckedField('layout-builder-content-preview'));
    $page->uncheckField('layout-builder-content-preview');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.layout-builder-block__content-preview-placeholder-label'));

    // Confirm that block content is not on page.
    $assert_session->pageTextNotContains($content_preview_body_text);
    $this->assertContextualLinks();

    // Check that content preview is still disabled on page reload.
    $this->getSession()->reload();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.layout-builder-block__content-preview-placeholder-label'));
    $assert_session->pageTextNotContains($content_preview_body_text);
    $this->assertContextualLinks();

    // Confirm repositioning blocks works with content preview disabled.
    $this->assertOrderInPage([$links_field_placeholder_label, $body_field_placeholder_label]);

    $region_content = '.layout__region--content';
    $links_block = "[data-layout-content-preview-placeholder-label='$links_field_placeholder_label']";
    $body_block = "[data-layout-content-preview-placeholder-label='$body_field_placeholder_label']";

    $assert_session->elementExists('css', $links_block . " div");
    $assert_session->elementExists('css', $body_block . " div");

    $this->sortableAfter($links_block, $body_block, $region_content);
    $assert_session->assertWaitOnAjaxRequest();

    // Check that the drag-triggered rebuild did not trigger content preview.
    $assert_session->pageTextNotContains($content_preview_body_text);

    // Check that drag successfully repositioned blocks.
    $this->assertOrderInPage([$body_field_placeholder_label, $links_field_placeholder_label]);

    // Check if block position maintained after enabling content preview.
    $this->assertTrue($page->hasUncheckedField('layout-builder-content-preview'));
    $page->checkField('layout-builder-content-preview');
    $this->assertNotEmpty($assert_session->waitForText($content_preview_body_text));
    $assert_session->pageTextContains($content_preview_body_text);
    $this->assertNotEmpty($assert_session->waitForText('Placeholder for the "Links" field'));
    $this->assertOrderInPage([$content_preview_body_text, 'Placeholder for the "Links" field']);
  }

  /**
   * Checks if contextual links are working properly.
   *
   * @internal
   */
  protected function assertContextualLinks(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->clickContextualLink('.block-field-blocknodebundle-for-this-particular-testbody', 'Configure');
    $this->waitForOffCanvasArea();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($this->assertSession()->waitForButton('Close'));
    $page->pressButton('Close');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
  }

  /**
   * Asserts that blocks in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings that should appear in the blocks.
   *
   * @internal
   */
  protected function assertOrderInPage(array $items): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $blocks = $page->findAll('css', '[data-layout-content-preview-placeholder-label]');

    // Filter will only return value if block contains expected text.
    $blocks_with_expected_text = array_filter($blocks, function ($block, $key) use ($items) {
      $block_text = $block->getText();
      return str_contains($block_text, $items[$key]);
    }, ARRAY_FILTER_USE_BOTH);

    $this->assertSameSize($items, $blocks_with_expected_text);
  }

}
