<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Field blocks tests for the override layout.
 *
 * @group layout_builder
 */
class ItemLayoutFieldBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    // We need more then one content type for this test.
    $this->createContentType(['type' => 'bundle_with_layout_overrides']);
    $this->createContentType(['type' => 'filler_bundle']);
  }

  /**
   * Tests configuring a field block for a user field.
   */
  public function testAddAjaxBlock() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Allow overrides for the layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_layout_overrides/display/default');
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // Start by creating a node of type with layout overrides.
    $node = $this->createNode([
      'type' => 'bundle_with_layout_overrides',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
    $node->save();

    // Open single item layout page.
    $this->drupalGet('node/1/layout');

    // Add a new block.
    $this->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();

    // Validate that only field blocks for layouted bundle are present.
    $valid_links = $page->findAll('css', 'a[href$="field_block%3Anode%3Abundle_with_layout_overrides%3Abody"]');
    $this->assertCount(1, $valid_links);
    $invalid_links = $page->findAll('css', 'a[href$="field_block%3Anode%3Afiller_bundle%3Abody"]');
    $this->assertCount(0, $invalid_links);
  }

}
