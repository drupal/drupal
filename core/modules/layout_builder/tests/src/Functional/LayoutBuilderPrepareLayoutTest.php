<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the ability to alter a layout builder element while preparing.
 *
 * @group layout_builder
 */
class LayoutBuilderPrepareLayoutTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'node',
    'layout_builder_element_test',
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

    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

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
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The third node title',
      'body' => [
        [
          'value' => 'The third node body',
        ],
      ],
    ]);
  }

  /**
   * Tests that we can alter a Layout Builder element while preparing.
   *
   * @see \Drupal\layout_builder_element_test\EventSubscriber\TestPrepareLayout;
   */
  public function testAlterPrepareLayout() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'administer blocks',
      'configure any layout',
      'administer node display',
      'configure all bundle_with_section_field node layout overrides',
    ]));

    // Add a block to the defaults.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $page->clickLink('Manage layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'Default block title');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Check the block is on the node page.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('Default block title');

    // When we edit the layout, it gets the static blocks.
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('Test static block title');
    $assert_session->pageTextNotContains('Default block title');
    $assert_session->pageTextContains('Test second static block title');

    // When we edit the second node, only the first event fires.
    $this->drupalGet('node/2/layout');
    $assert_session->pageTextContains('Test static block title');
    $assert_session->pageTextNotContains('Default block title');
    $assert_session->pageTextNotContains('Test second static block title');

    // When we edit the third node, the default exists PLUS our static block.
    $this->drupalGet('node/3/layout');
    $assert_session->pageTextNotContains('Test static block title');
    $assert_session->pageTextContains('Default block title');
    $assert_session->pageTextContains('Test second static block title');
  }

}
