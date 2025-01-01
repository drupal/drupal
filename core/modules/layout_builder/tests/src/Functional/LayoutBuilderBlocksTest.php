<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\node\Entity\Node;
use Drupal\views\Entity\View;

// cspell:ignore blocktest

/**
 * Tests the Layout Builder UI with blocks.
 *
 * @group layout_builder
 */
class LayoutBuilderBlocksTest extends LayoutBuilderTestBase {

  /**
   * Tests that block plugins can define custom attributes and contextual links.
   */
  public function testPluginsProvidingCustomAttributesAndContextualLinks(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('Layout Builder Test Plugin');
    $page->pressButton('Add section');
    $page->clickLink('Add block');
    $page->clickLink('Test Attributes');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $this->drupalGet('node/1');

    $assert_session->elementExists('css', '.attribute-test-class');
    $assert_session->elementExists('css', '[custom-attribute=test]');
    $assert_session->elementExists('css', 'div[data-contextual-id*="layout_builder_test"]');
  }

  /**
   * Tests preview-aware layout & block plugins.
   */
  public function testPreviewAwarePlugins(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $page->clickLink('Manage layout');
    $page->clickLink('Add section');
    $page->clickLink('Layout Builder Test Plugin');
    $page->pressButton('Add section');
    $page->clickLink('Add block');
    $page->clickLink('Preview-aware block');
    $page->pressButton('Add block');

    $assert_session->elementExists('css', '.go-birds-preview');
    $assert_session->pageTextContains('The block template is being previewed.');
    $assert_session->pageTextContains('This block is being rendered in preview mode.');

    $page->pressButton('Save layout');
    $this->drupalGet('node/1');

    $assert_session->elementNotExists('css', '.go-birds-preview');
    $assert_session->pageTextNotContains('The block template is being previewed.');
    $assert_session->pageTextContains('This block is being rendered normally.');
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderChooseBlocksAlter(): void {
    // See layout_builder_test_plugin_filter_block__layout_builder_alter().
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    // From the manage display page, go to manage the layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');

    // Add a new block.
    $this->clickLink('Add block');

    // Verify that blocks not modified are present.
    $assert_session->linkExists('Powered by Drupal');
    $assert_session->linkExists('Default revision');

    // Verify that blocks explicitly removed are not present.
    $assert_session->linkNotExists('Help');
    $assert_session->linkNotExists('Sticky at top of lists');
    $assert_session->linkNotExists('Main page content');
    $assert_session->linkNotExists('Page title');
    $assert_session->linkNotExists('Messages');
    $assert_session->linkNotExists('Help');
    $assert_session->linkNotExists('Tabs');
    $assert_session->linkNotExists('Primary admin actions');

    // Verify that Changed block is not present on first section.
    $assert_session->linkNotExists('Changed');

    // Go back to Manage layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->clickLink('Manage layout');

    // Add a new section.
    $this->clickLink('Add section', 1);
    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->buttonExists('Add section');
    $this->getSession()->getPage()->pressButton('Add section');
    // Add a new block to second section.
    $this->clickLink('Add block', 1);

    // Verify that Changed block is present on second section.
    $assert_session->linkExists('Changed');
  }

  /**
   * Tests that deleting a View block used in Layout Builder works.
   */
  public function testDeletedView(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Enable overrides.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $this->clickLink('Add block');
    $this->clickLink('Test Block View');
    $page->pressButton('Add block');

    $assert_session->pageTextContains('Test Block View');
    $assert_session->elementExists('css', '.block-views-blocktest-block-view-block-1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Test Block View');
    $assert_session->elementExists('css', '.block-views-blocktest-block-view-block-1');

    View::load('test_block_view')->delete();
    $this->drupalGet('node/1');
    // Node can be loaded after deleting the View.
    $assert_session->pageTextContains(Node::load(1)->getTitle());
    $assert_session->pageTextNotContains('Test Block View');
  }

  /**
   * Tests the usage of placeholders for empty blocks.
   *
   * @see \Drupal\Core\Render\PreviewFallbackInterface::getPreviewFallbackString()
   * @see \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray::onBuildRender()
   */
  public function testBlockPlaceholder(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display/default/layout");

    // Add a block whose content is controlled by state and is empty by default.
    $this->clickLink('Add block');
    $this->clickLink('Test block caching');
    $page->fillField('settings[label]', 'The block label');
    $page->pressButton('Add block');

    $block_content = 'I am content';
    $placeholder_content = 'Placeholder for the "The block label" block';

    // The block placeholder is displayed and there is no content.
    $assert_session->pageTextContains($placeholder_content);
    $assert_session->pageTextNotContains($block_content);

    // Set block content and reload the page.
    \Drupal::keyValue('block_test')->set('content', $block_content);
    $this->getSession()->reload();

    // The block placeholder is no longer displayed and the content is visible.
    $assert_session->pageTextNotContains($placeholder_content);
    $assert_session->pageTextContains($block_content);
  }

  /**
   * Tests the ability to use a specified block label for field blocks.
   */
  public function testFieldBlockLabel(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display/default/layout");

    // Add a body block whose label will be overridden.
    $this->clickLink('Add block');
    $this->clickLink('Body');

    // Enable the Label Display and set the Label to a modified field
    // block label.
    $modified_field_block_label = 'Modified Field Block Label';
    $page->checkField('settings[label_display]');
    $page->fillField('settings[label]', $modified_field_block_label);

    // Save the block and layout.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Revisit the default layout view mode page.
    $this->drupalGet("$field_ui_prefix/display/default/layout");

    // The modified field block label is displayed.
    $assert_session->pageTextContains($modified_field_block_label);
  }

  /**
   * Tests the Block UI when Layout Builder is installed.
   */
  public function testBlockUiListing(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));

    $this->drupalGet('admin/structure/block');
    $page->clickLink('Place block');

    // Ensure that blocks expected to appear are available.
    $assert_session->pageTextContains('Test HTML block');
    $assert_session->pageTextContains('Block test');
    // Ensure that blocks not expected to appear are not available.
    $assert_session->pageTextNotContains('Body');
    $assert_session->pageTextNotContains('Content fields');
  }

}
