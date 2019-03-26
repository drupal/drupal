<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Zend\Stdlib\ArrayUtils;

/**
 * Tests toggling of content preview.
 *
 * @group layout_builder
 */
class ContentPreviewToggleTest extends WebDriverTestBase {

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
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'bundle_for_this_particular_test']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
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

    $this->drupalPostForm(
      'admin/structure/types/manage/bundle_for_this_particular_test/display/default',
      ['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE],
      'Save'
    );

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

    // Wait for preview content hide() to complete.
    $this->waitForNoElement('[data-layout-content-preview-placeholder-label] .field--name-body:visible');
    $assert_session->pageTextNotContains($content_preview_body_text);
    $this->assertContextualLinks();

    // Check that content preview is still disabled on page reload.
    $this->getSession()->reload();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.layout-builder-block__content-preview-placeholder-label'));
    $assert_session->pageTextNotContains($content_preview_body_text);
    $this->assertContextualLinks();

    // Confirm repositioning blocks works with content preview disabled.
    $this->assertOrderInPage([$links_field_placeholder_label, $body_field_placeholder_label]);

    $links_block_placeholder_child = $assert_session->elementExists('css', "[data-layout-content-preview-placeholder-label='$links_field_placeholder_label'] div");
    $body_block_placeholder_child = $assert_session->elementExists('css', "[data-layout-content-preview-placeholder-label='$body_field_placeholder_label'] div");
    $body_block_placeholder_child->dragTo($links_block_placeholder_child);
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
   */
  protected function assertContextualLinks() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->clickContextualLink('.block-field-blocknodebundle-for-this-particular-testbody', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElement('css', "#drupal-off-canvas"));
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($this->assertSession()->waitForButton('Close'));
    $page->pressButton('Close');
    $this->waitForNoElement('#drupal-off-canvas');
  }

  /**
   * Asserts that blocks in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings that should appear in the blocks.
   */
  protected function assertOrderInPage(array $items) {
    $session = $this->getSession();
    $page = $session->getPage();
    $blocks = $page->findAll('css', '[data-layout-content-preview-placeholder-label]');

    // Filter will only return value if block contains expected text.
    $blocks_with_expected_text = ArrayUtils::filter($blocks, function ($block, $key) use ($items) {
      $block_text = $block->getText();
      return strpos($block_text, $items[$key]) !== FALSE;
    }, ArrayUtils::ARRAY_FILTER_USE_BOTH);

    $this->assertCount(count($items), $blocks_with_expected_text);
  }

  /**
   * Waits for an element to be removed from the page.
   *
   * @param string $selector
   *   CSS selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   *
   * @todo Remove in https://www.drupal.org/node/2892440.
   */
  protected function waitForNoElement($selector, $timeout = 10000) {
    $condition = "(typeof jQuery !== 'undefined' && jQuery('$selector').length === 0)";
    $this->assertJsCondition($condition, $timeout);
  }

}
