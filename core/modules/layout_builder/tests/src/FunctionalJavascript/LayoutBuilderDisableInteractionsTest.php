<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use WebDriver\Exception\UnknownError;

/**
 * Tests the Layout Builder disables interactions of rendered blocks.
 *
 * @group layout_builder
 */
class LayoutBuilderDisableInteractionsTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'filter',
    'filter_test',
    'layout_builder',
    'node',
    'search',
    'contextual',
    'layout_builder_test_css_transitions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

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
        'value' => '<iframe id="iframe-that-should-be-disabled" width="560" height="315" src="https://www.youtube.com/embed/gODZzSOelss" frameborder="0"></iframe>',
        'format' => 'full_html',
      ],
    ])->save();
  }

  /**
   * Tests that forms and links are disabled in the Layout Builder preview.
   */
  public function testFormsLinksDisabled() {
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

    $this->drupalPostForm("$field_ui_prefix/display", ['layout[enabled]' => TRUE], 'Save');
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

    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');
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
  protected function addBlock($block_link_text, $rendered_locator) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add a new block.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#layout-builder a:contains(\'Add Block\')'));
    $this->clickLink('Add Block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists($block_link_text);
    $this->clickLink($block_link_text);

    // Wait for off-canvas dialog to reopen with block form.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', ".layout-builder-add-block"));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add Block');

    // Wait for block form to be rendered in the Layout Builder.
    $this->assertNotEmpty($assert_session->waitForElement('css', $rendered_locator));
  }

  /**
   * Checks if element is unclickable.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element being checked for.
   */
  protected function assertElementUnclickable(NodeElement $element) {
    try {
      $element->click();
      $tag_name = $element->getTagName();
      $this->fail(new FormattableMarkup("@tag_name was clickable when it shouldn't have been", ['@tag_name' => $tag_name]));
    }
    catch (UnknownError $e) {
      $this->assertContains('is not clickable at point', $e->getMessage());
    }
  }

  /**
   * Asserts that forms, links, and iframes in preview are non-interactive.
   */
  protected function assertLinksFormIframeNotInteractive() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertNotEmpty($assert_session->waitForElement('css', '.block-search'));
    $searchButton = $assert_session->buttonExists('Search');
    $this->assertElementUnclickable($searchButton);
    $assert_session->linkExists('Take me away');
    $this->assertElementUnclickable($page->findLink('Take me away'));
    $iframe = $assert_session->elementExists('css', '#iframe-that-should-be-disabled');
    $this->assertElementUnclickable($iframe);
  }

  /**
   * Confirms that Layout Builder contextual links remain active.
   */
  protected function assertContextualLinksClickable() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->getUrl());

    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody [data-contextual-id^="layout_builder_block"]', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ui-dialog-titlebar [title="Close"]'));
    $page->pressButton('Close');
    $this->assertNoElementAfterWait('#drupal-off-canvas');

    // Run the steps a second time after closing dialog, which reverses the
    // order that behaviors.layoutBuilderDisableInteractiveElements and
    // contextual link initialization occurs.
    $this->clickContextualLink('.block-field-blocknodebundle-with-section-fieldbody [data-contextual-id^="layout_builder_block"]', 'Configure');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
  }

  /**
   * Waits for an element to be removed from the page.
   *
   * @param string $selector
   *   CSS selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) Custom message to display with the assertion.
   *
   * @todo: Remove after https://www.drupal.org/project/drupal/issues/2892440
   */
  public function assertNoElementAfterWait($selector, $timeout = 10000, $message = '') {
    $page = $this->getSession()->getPage();
    if ($message === '') {
      $message = "Element '$selector' was not on the page after wait.";
    }
    $this->assertTrue($page->waitFor($timeout / 1000, function () use ($page, $selector) {
      return empty($page->find('css', $selector));
    }), $message);
  }

}
