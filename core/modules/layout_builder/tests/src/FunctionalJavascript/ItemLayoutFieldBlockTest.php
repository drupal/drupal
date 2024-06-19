<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));

    // We need more then one content type for this test.
    $this->createContentType(['type' => 'bundle_with_layout_overrides']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_layout_overrides.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
    $this->createContentType(['type' => 'filler_bundle']);
  }

  /**
   * Tests configuring a field block for a user field.
   */
  public function testAddAjaxBlock(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

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

    // Validate that only field blocks for layout bundles are present.
    $valid_links = $page->findAll('css', 'a[href$="field_block%3Anode%3Abundle_with_layout_overrides%3Abody"]');
    $this->assertCount(1, $valid_links);
    $invalid_links = $page->findAll('css', 'a[href$="field_block%3Anode%3Afiller_bundle%3Abody"]');
    $this->assertCount(0, $invalid_links);
  }

}
