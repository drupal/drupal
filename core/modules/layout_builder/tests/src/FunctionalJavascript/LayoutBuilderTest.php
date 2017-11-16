<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderTest extends JavascriptTestBase {

  use ContextualLinkClickTrait;
  use PageReloadHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'node',
    'block_content',
    'field_ui',
    'layout_test',
  ];

  /**
   * The node to customize with Layout Builder.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());
    BlockContent::create([
      'info' => 'My custom block',
      'type' => 'basic',
      'body' => [
        [
          'value' => 'This is the block content',
          'format' => filter_default_format(),
        ],
      ],
    ])->save();

    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
    ], 'foobar'));
  }

  /**
   * Tests the Layout Builder UI.
   */
  public function testLayoutBuilderUi() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Ensure the block is not displayed initially.
    $this->drupalGet($this->node->toUrl('canonical'));
    $assert_session->pageTextContains('The node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    // Enable layout support.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // The existing content is still shown until overridden.
    $this->drupalGet($this->node->toUrl('canonical'));
    $assert_session->pageTextContains('The node body');

    // Enter the layout editing mode.
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $this->markCurrentPage();
    $assert_session->pageTextNotContains('The node body');
    $assert_session->linkExists('Add Section');
    $assert_session->linkExists('Add Block');

    // Add a new block.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->elementExists('css', '#drupal-off-canvas');

    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $this->waitForOffCanvasForm('layout_builder_add_block');

    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');

    // Save the new block, and ensure it is displayed on the page.
    $page->pressButton('Add Block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#drupal-off-canvas');

    $assert_session->addressEquals($this->node->toUrl('layout-builder'));
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $this->assertPageNotReloaded();

    // Until the layout is saved, the new block is not visible on the node page.
    $this->drupalGet($this->node->toUrl('canonical'));
    $assert_session->pageTextNotContains('Powered by Drupal');

    // When returning to the layout edit mode, the new block is visible.
    $this->drupalGet($this->node->toUrl('layout-builder'));
    $assert_session->pageTextContains('Powered by Drupal');

    // Save the layout, and the new block is visible.
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->addressEquals($this->node->toUrl('canonical'));
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $assert_session->elementExists('css', '.layout');

    // Drag one block from one region to another.
    $this->drupalGet($this->node->toUrl('layout-builder'));
    $this->markCurrentPage();

    $assert_session->linkExists('Add Section');
    $this->clickLink('Add Section');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->elementNotExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextNotContains('css', '.layout__region--second', 'Powered by Drupal');
    // Drag the block from one layout to another.
    $page->find('css', '.layout__region--content .block-system-powered-by-block')->dragTo($page->find('css', '.layout__region--second'));
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure the drag succeeded.
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');
    $this->assertPageNotReloaded();

    // Ensure the drag persisted after reload.
    $this->drupalGet($this->node->toUrl('layout-builder'));
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');

    // Ensure the drag persisted after save.
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');

    // Configure a block.
    $this->drupalGet($this->node->toUrl('layout-builder'));
    $this->markCurrentPage();

    $this->clickContextualLink('.block-system-powered-by-block', 'Configure');
    $this->waitForOffCanvasForm('layout_builder_update_block');

    $page->fillField('settings[label]', 'This is the new label');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#drupal-off-canvas');

    $assert_session->addressEquals($this->node->toUrl('layout-builder'));
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the new label');
    $assert_session->pageTextNotContains('This is the label');

    // Remove a block.
    $this->clickContextualLink('.block-system-powered-by-block', 'Remove block');
    $this->waitForOffCanvasForm('layout_builder_remove_block');

    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#drupal-off-canvas');

    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkExists('Add Block');
    $assert_session->addressEquals($this->node->toUrl('layout-builder'));
    $this->assertPageNotReloaded();

    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->elementExists('css', '.layout');

    // Test deriver-based blocks.
    $this->drupalGet($this->node->toUrl('layout-builder'));
    $this->markCurrentPage();

    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists('My custom block');
    $this->clickLink('My custom block');
    $this->waitForOffCanvasForm('layout_builder_add_block');
    $page->pressButton('Add Block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('This is the block content');

    // Remove both sections.
    $assert_session->linkExists('Remove section');
    $this->clickLink('Remove section');
    $this->waitForOffCanvasForm('layout_builder_remove_section');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists('Remove section');
    $this->clickLink('Remove section');
    $this->waitForOffCanvasForm('layout_builder_remove_section');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('This is the block content');
    $assert_session->linkNotExists('Add Block');
    $this->assertPageNotReloaded();

    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->elementNotExists('css', '.layout');

    // Removing all sections results in the original display being used.
    $assert_session->addressEquals($this->node->toUrl('canonical'));
    $assert_session->pageTextContains('The node body');
  }

  /**
   * Tests configurable layouts.
   */
  public function testConfigurableLayouts() {
    entity_get_display('node', 'bundle_with_section_field', 'full')
      ->setThirdPartySetting('layout_builder', 'allow_custom', TRUE)
      ->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($this->node->toUrl('layout-builder'));
    $this->markCurrentPage();

    $assert_session->linkExists('Add Section');
    $this->clickLink('Add Section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#drupal-off-canvas');

    $assert_session->linkExists('One column');
    $this->clickLink('One column');
    $assert_session->assertWaitOnAjaxRequest();

    // Add another section.
    $assert_session->linkExists('Add Section');
    $this->clickLink('Add Section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#drupal-off-canvas');

    $assert_session->linkExists('Layout plugin (with settings)');
    $this->clickLink('Layout plugin (with settings)');
    $this->waitForOffCanvasForm('layout_builder_configure_section');
    $assert_session->fieldExists('layout_settings[setting_1]');
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->elementNotExists('css', '#drupal-off-canvas');
    $assert_session->pageTextContains('Default');
    $assert_session->linkExists('Add Block');

    // Configure the existing section.
    $assert_session->linkExists('Configure section');
    $this->clickLink('Configure section');
    $this->waitForOffCanvasForm('layout_builder_configure_section');
    $page->fillField('layout_settings[setting_1]', 'Test setting value');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#drupal-off-canvas');
    $assert_session->pageTextContains('Test setting value');
    $this->assertPageNotReloaded();
  }

  /**
   * Tests bypassing the Off Canvas dialog.
   */
  public function testLayoutNoDialog() {
    entity_get_display('node', 'bundle_with_section_field', 'full')
      ->setThirdPartySetting('layout_builder', 'allow_custom', TRUE)
      ->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set up a layout with one section.
    $this->drupalGet(Url::fromRoute('layout_builder.choose_section', [
      'entity_type_id' => 'node',
      'entity' => 1,
      'delta' => 0,
    ]));
    $assert_session->linkExists('One column');
    $this->clickLink('One column');

    // Add a block.
    $this->drupalGet(Url::fromRoute('layout_builder.add_block', [
      'entity_type_id' => 'node',
      'entity' => 1,
      'delta' => 0,
      'region' => 'content',
      'plugin_id' => 'system_powered_by_block',
    ]));
    $assert_session->elementNotExists('css', '#drupal-off-canvas');
    $page->fillField('settings[label]', 'The block label');
    $page->fillField('settings[label_display]', TRUE);
    $page->pressButton('Add Block');

    $assert_session->addressEquals($this->node->toUrl('layout-builder'));
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('The block label');

    // Remove the section.
    $this->drupalGet(Url::fromRoute('layout_builder.remove_section', [
      'entity_type_id' => 'node',
      'entity' => 1,
      'delta' => 0,
    ]));
    $page->pressButton('Remove');
    $assert_session->addressEquals($this->node->toUrl('layout-builder'));
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('The block label');
    $assert_session->linkNotExists('Add Block');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Workaround for https://www.drupal.org/node/2918718.
   */
  protected function clickContextualLink($selector, $link_locator, $force_visible = TRUE) {
    $assert_session = $this->assertSession();

    if ($force_visible) {
      $this->getSession()->executeScript("jQuery('{$selector} .contextual button').removeClass('visually-hidden');");
      $assert_session->waitForElementVisible('css', '.contextual button');
    }

    $element = $this->getSession()->getPage()->find('css', $selector);
    $link = $element->findLink($link_locator);
    if (!$link) {
      $this->fail("Link $link_locator was found");
    }
    else {
      // If the link is not visible, click the contextual link button first.
      if (!$link->isVisible()) {
        $element->find('css', '.contextual button')->press();
        $assert_session->waitForLink($link_locator);
      }
      $this->assertTrue($link->isVisible(), "Link $link_locator is visible.");
      $link->click();
    }

    if ($force_visible) {
      $this->getSession()->executeScript("jQuery('{$selector} .contextual .trigger').addClass('visually-hidden');");
      $assert_session->assertWaitOnAjaxRequest();
    }
  }

  /**
   * Waits for the specified form and returns it when available and visible.
   *
   * @param string $expected_form_id
   *   The expected form ID.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The form element if found and visible, NULL if not.
   */
  protected function waitForOffCanvasForm($expected_form_id, $timeout = 10000) {
    $page = $this->getSession()->getPage();
    return $page->waitFor($timeout / 1000, function () use ($page, $expected_form_id) {
      // Ensure the form ID exists, is visible, and has the correct value.
      $form_id_element = $page->find('hidden_field_selector', ['hidden_field', 'form_id']);
      if (!$form_id_element || !$form_id_element->isVisible() || $expected_form_id !== $form_id_element->getValue()) {
        return NULL;
      }

      // Ensure the off canvas dialog is visible.
      $off_canvas = $page->find('css', '#drupal-off-canvas');
      if (!$off_canvas || !$off_canvas->isVisible()) {
        return NULL;
      }
      return $form_id_element;
    });
  }

}
