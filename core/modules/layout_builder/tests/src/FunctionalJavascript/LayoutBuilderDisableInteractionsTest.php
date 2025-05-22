<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalJavascriptTests\JSWebAssert;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\system\Traits\OffCanvasTestTrait;

// cspell:ignore blocknodebundle fieldbody

/**
 * Tests the Layout Builder disables interactions of rendered blocks.
 *
 * @group layout_builder
 * @group #slow
 */
class LayoutBuilderDisableInteractionsTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;
  use OffCanvasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'field_ui',
    'filter',
    'filter_test',
    'layout_builder',
    'node',
    'search',
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

    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'Node body',
        ],
      ],
    ]);

    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => 1,
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());

    BlockContent::create([
      'type' => 'basic',
      'info' => 'Block with link',
      'body' => [
        // Create a link that should be disabled in Layout Builder preview.
        'value' => '<a id="link-that-should-be-disabled" href="/search/node">Take me away</a>',
        'format' => 'full_html',
      ],
    ])->save();

    BlockContent::create([
      'type' => 'basic',
      'info' => 'Block with iframe',
      'body' => [
        // Add iframe that should be non-interactive in Layout Builder preview.
        'value' => '<iframe id="iframe-that-should-be-disabled" width="1" height="1" src="https://www.youtube.com/embed/gODZzSOelss" frameborder="0"></iframe>',
        'format' => 'full_html',
      ],
    ])->save();
  }

  /**
   * Tests that forms and links are disabled in the Layout Builder preview.
   */
  public function testFormsLinksDisabled(): void {
    // Resize window due to bug in Chromedriver when clicking on overlays over
    // iFrames.
    // @see https://bugs.chromium.org/p/chromedriver/issues/detail?id=2758
    $this->getSession()->resizeWindow(1200, 1200);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
      'search content',
      'access contextual links',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    $this->drupalGet("{$field_ui_prefix}/display");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');

    // Add a block with a form, another with a link, and one with an iframe.
    $this->addBlock('Search form', '#layout-builder .search-block-form');
    $this->addBlock('Block with link', '#link-that-should-be-disabled');
    $this->addBlock('Block with iframe', '#iframe-that-should-be-disabled');

    // Ensure the links and forms are disabled using the defaults before the
    // layout is saved.
    $this->assertLinksFormIframeNotInteractive();

    $page->pressButton('Save layout');
    $this->clickLink('Manage layout');

    // Ensure the links and forms are disabled using the defaults.
    $this->assertLinksFormIframeNotInteractive();

    // Ensure contextual links were not disabled.
    $this->assertContextualLinksClickable();

    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1/layout');

    // Ensure the links and forms are also disabled in using the override.
    $this->assertLinksFormIframeNotInteractive();

    // Ensure contextual links were not disabled.
    $this->assertContextualLinksClickable();
  }

  /**
   * Adds a block in the Layout Builder.
   *
   * @param string $block_link_text
   *   The link text to add the block.
   * @param string $rendered_locator
   *   The CSS locator to confirm the block was rendered.
   */
  protected function addBlock($block_link_text, $rendered_locator): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add a new block.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#layout-builder a:contains(\'Add block\')'));
    $this->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists($block_link_text);
    $this->clickLink($block_link_text);

    // Wait for off-canvas dialog to reopen with block form.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', ".layout-builder-add-block"));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');

    // Wait for block form to be rendered in the Layout Builder.
    $this->assertNotEmpty($assert_session->waitForElement('css', $rendered_locator));
  }

  /**
   * Checks if element is not clickable.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element being checked for.
   *
   * @internal
   */
  protected function assertElementNotClickable(NodeElement $element): void {
    try {
      $element->click();
      $tag_name = $element->getTagName();
      $this->fail("$tag_name was clickable when it shouldn't have been");
    }
    catch (\Exception $e) {
      $this->assertTrue(JSWebAssert::isExceptionNotClickable($e));
    }
  }

  /**
   * Asserts that forms, links, and iframes in preview are non-interactive.
   *
   * @internal
   */
  protected function assertLinksFormIframeNotInteractive(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertNotEmpty($assert_session->waitForElement('css', '.block-search'));
    $searchButton = $assert_session->buttonExists('Search');
    $this->assertElementNotClickable($searchButton);
    $assert_session->linkExists('Take me away');
    $this->assertElementNotClickable($page->findLink('Take me away'));
    $iframe = $assert_session->elementExists('css', '#iframe-that-should-be-disabled');
    $this->assertElementNotClickable($iframe);
  }

  /**
   * Confirms that Layout Builder contextual links remain active.
   *
   * @internal
   */
  protected function assertContextualLinksClickable(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->getUrl());

    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody [data-contextual-id^="layout_builder_block"]', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ui-dialog-titlebar [title="Close"]'));
    // We explicitly wait for the off-canvas area to be fully resized before
    // trying to press the Close button, instead of waiting for the Close button
    // itself to become visible. This is to prevent a regularly occurring random
    // test failure.
    $this->waitForOffCanvasArea();
    $page->pressButton('Close');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');

    // Run the steps a second time after closing dialog, which reverses the
    // order that behaviors.layoutBuilderDisableInteractiveElements and
    // contextual link initialization occurs.
    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody [data-contextual-id^="layout_builder_block"]', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $page->pressButton('Close');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $this->assertContextualLinkRetainsMouseup();
  }

  /**
   * Makes sure contextual links respond to mouseup event.
   *
   * Disabling interactive elements includes preventing defaults on the mouseup
   * event for links. However, this should not happen with contextual links.
   * This is confirmed by clicking a contextual link then moving the mouse
   * pointer. If mouseup is working properly, the draggable element will not
   * be moved by the pointer moving.
   *
   * @internal
   */
  protected function assertContextualLinkRetainsMouseup(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $body_field_selector = '.block-field-blocknodebundle-with-section-fieldbody';

    $body_block = $page->find('css', $body_field_selector);
    $this->assertNotEmpty($body_block);

    // Get the current Y position of the body block.
    $body_block_top_position = $this->getElementVerticalPosition($body_field_selector, 'top');

    $body_block_contextual_link_button = $body_block->find('css', '.trigger');
    $this->assertNotEmpty($body_block_contextual_link_button);

    // If the body block contextual link is hidden, make it visible.
    if ($body_block_contextual_link_button->hasClass('visually-hidden')) {
      $this->toggleContextualTriggerVisibility($body_field_selector);
    }

    // For the purposes of this test, the contextual link must be accessed with
    // discrete steps instead of using ContextualLinkClickTrait.
    $body_block->pressButton('Open configuration options');
    $body_block->clickLink('Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();

    // After the contextual link opens the dialog, move the mouse pointer
    // elsewhere on the page. If mouse up were not working correctly this would
    // actually drag the body field too.
    $this->getSession()->getDriver()->mouseOver('.//*[@id="iframe-that-should-be-disabled"]');

    $new_body_block_bottom_position = $this->getElementVerticalPosition($body_field_selector, 'bottom');
    $iframe_top_position = $this->getElementVerticalPosition('#iframe-that-should-be-disabled', 'top');

    $minimum_distance_mouse_moved = $iframe_top_position - $new_body_block_bottom_position;
    $this->assertGreaterThan(200, $minimum_distance_mouse_moved, 'The mouse moved at least 200 pixels');

    // If mouseup is working properly, the body block should be nearly in same
    // position as it was when $body_block_y_position was declared. It will have
    // moved slightly because the current block being configured will have a
    // border that was not present when the dialog was not open.
    $new_body_block_top_position = $this->getElementVerticalPosition($body_field_selector, 'top');
    $distance_body_block_moved = abs($body_block_top_position - $new_body_block_top_position);
    // Confirm that body moved only slightly compared to the distance the mouse
    // moved and therefore was not dragged when the mouse moved.
    $this->assertGreaterThan($distance_body_block_moved * 20, $minimum_distance_mouse_moved);
  }

  /**
   * Gets the element position.
   *
   * @param string $css_selector
   *   The CSS selector of the element.
   * @param string $position_type
   *   The position type to get, either 'top' or 'bottom'.
   *
   * @return int
   *   The element position.
   */
  protected function getElementVerticalPosition($css_selector, $position_type): int {
    $this->assertContains($position_type, ['top', 'bottom'], 'Expected position type.');
    return (int) $this->getSession()->evaluateScript("document.querySelector('$css_selector').getBoundingClientRect().$position_type + window.pageYOffset");
  }

  /**
   * Moves mouse pointer to location of $selector.
   *
   * @param string $selector
   *   CSS selector.
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
   *   $this->getSession()->getDriver()->mouseOver() instead.
   *
   * @see https://www.drupal.org/node/3460567
   */
  protected function movePointerTo($selector): void {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use $this->getSession()->getDriver()->mouseOver() instead. See https://www.drupal.org/node/3460567', E_USER_DEPRECATED);
    $driver_session = $this->getSession()->getDriver()->getWebDriverSession();
    $element = $driver_session->element('css selector', $selector);
    $driver_session->moveto(['element' => $element->getID()]);
  }

}
