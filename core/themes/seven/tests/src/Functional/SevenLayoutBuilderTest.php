<?php

namespace Drupal\Tests\seven\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Seven theme.
 *
 * @group seven
 */
class SevenLayoutBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'layout_builder',
    'layout_builder_views_test',
    'layout_test',
    'field_ui',
    'block',
    'block_test',
    'node',
    'layout_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block', ['region' => 'header']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header']);

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);
  }

  /**
   * Tests the layout builder has expected contextual links with Seven.
   *
   * @see seven.theme
   */
  public function testContextualLinks() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkNotExists('Manage layout');
    $assert_session->fieldDisabled('layout[allow_custom]');

    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');

    // Add a new block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');

    // Test that the block has the contextual class applied and the container
    // for contextual links.
    $assert_session->elementExists('css', 'div.block-system-powered-by-block.contextual-region div[data-contextual-id]');

    // Ensure other blocks do not have contextual links.
    $assert_session->elementExists('css', 'div.block-page-title-block');
    $assert_session->elementNotExists('css', 'div.block-page-title-block.contextual-region div[data-contextual-id]');
  }

}
