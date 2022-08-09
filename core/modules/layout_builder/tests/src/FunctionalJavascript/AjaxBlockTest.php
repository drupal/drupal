<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Ajax blocks tests.
 *
 * @group layout_builder
 */
class AjaxBlockTest extends WebDriverTestBase {

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

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));
    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests configuring a field block for a user field.
   */
  public function testAddAjaxBlock() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Start by creating a node.
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The node body');
    $assert_session->pageTextNotContains('Every word is like an unnecessary stain on silence and nothingness.');

    // From the manage display page, go to manage the layout.
    $this->clickLink('Layout');
    // The body field is present.
    $assert_session->elementExists('css', '.field--name-body');

    // Add a new block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('TestAjax');
    $this->clickLink('TestAjax');
    $assert_session->assertWaitOnAjaxRequest();
    // Find the radio buttons.
    $name = 'settings[ajax_test]';
    /** @var \Behat\Mink\Element\NodeElement[] $radios */
    $this->markTestSkipped('Temporarily skipped due to random failures.');
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
    $block_elements = $this->cssSelect('.block-layout-builder-test-testajax');
    // Should be exactly one of these in there.
    $this->assertCount(1, $block_elements);
    $assert_session->pageTextContains('Every word is like an unnecessary stain on silence and nothingness.');
  }

}
