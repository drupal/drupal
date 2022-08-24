<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\system\Traits\OffCanvasTestTrait;

/**
 * Ajax blocks tests.
 *
 * @group layout_builder
 */
class AjaxBlockTest extends WebDriverTestBase {

  use OffCanvasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'datetime',
    'layout_builder',
    'user',
    'layout_builder_test',
    'off_canvas_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]);
    $user->save();
    $this->drupalLogin($user);
    $this->createContentType(['type' => 'bundle_with_section_field']);
  }

  /**
   * Tests configuring a field block for a user field.
   */
  public function testAddAjaxBlock() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Start by creating a node.
    $node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
    $node->save();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The node body');
    $assert_session->pageTextNotContains('Every word is like an unnecessary stain on silence and nothingness.');
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("{$field_ui_prefix}/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default/layout");
    // The body field is present.
    $assert_session->elementExists('css', '.field--name-body');

    // Add a new block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $this->waitForOffCanvasArea();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('TestAjax');
    $this->clickLink('TestAjax');
    $this->waitForOffCanvasArea();
    $assert_session->assertWaitOnAjaxRequest();
    // Find the radio buttons.
    $name = 'settings[ajax_test]';
    /** @var \Behat\Mink\Element\NodeElement[] $radios */
    $radios = $this->assertSession()->fieldExists($name);
    // Click them both a couple of times.
    foreach ([1, 2] as $rounds) {
      foreach ($radios as $radio) {
        $radio->click();
        $assert_session->assertWaitOnAjaxRequest();
      }
    }
    // Then add the block.
    $assert_session->waitForElementVisible('named', ['button', 'Add block'])->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForElementVisible('css', '.block-layout-builder-test-testajax');
    $block_elements = $this->cssSelect('.block-layout-builder-test-testajax');
    // Should be exactly one of these in there.
    $this->assertCount(1, $block_elements);
    $assert_session->pageTextContains('Every word is like an unnecessary stain on silence and nothingness.');
  }

}
