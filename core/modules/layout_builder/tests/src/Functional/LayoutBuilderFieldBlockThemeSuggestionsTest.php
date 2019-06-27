<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests field block template suggestions.
 *
 * @group layout_builder
 */
class LayoutBuilderFieldBlockThemeSuggestionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'node',
    'layout_builder_field_block_theme_suggestions_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'A node title',
      'body' => [
        [
          'value' => 'This is content that the template should not render',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->drupalPostForm(NULL, ['layout[enabled]' => TRUE], 'Save');
  }

  /**
   * Tests that of view mode specific field templates are suggested.
   */
  public function testFieldBlockViewModeTemplates() {
    $assert_session = $this->assertSession();

    $this->drupalGet('node/1');
    // Confirm that content is displayed by layout builder.
    $assert_session->elementExists('css', '.block-layout-builder');
    // Text that only appears in the view mode specific template.
    $assert_session->pageTextContains('I am a field template for a specific view mode!');
    // The content of the body field should not be visible because it is
    // displayed via a template that does not render it.
    $assert_session->pageTextNotContains('This is content that the template should not render');
  }

}
