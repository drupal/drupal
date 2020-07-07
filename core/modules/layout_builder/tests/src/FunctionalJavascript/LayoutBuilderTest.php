<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;
  use LayoutBuilderSortTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'field_ui',
    'layout_builder',
    'layout_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * The node to customize with Layout Builder.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * A string used to mark the current page.
   *
   * @var string
   *
   * @todo Remove in https://www.drupal.org/project/drupal/issues/2909782.
   */
  private $pageReloadMarker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    $layout_url = 'node/1/layout';
    $node_url = 'node/1';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Ensure the block is not displayed initially.
    $this->drupalGet($node_url);
    $assert_session->pageTextContains('The node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    $this->enableLayoutsForBundle('admin/structure/types/manage/bundle_with_section_field/display', TRUE);

    // The existing content is still shown until overridden.
    $this->drupalGet($node_url);
    $assert_session->pageTextContains('The node body');

    // Enter the layout editing mode.
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $this->markCurrentPage();
    $assert_session->pageTextContains('The node body');
    $assert_session->linkExists('Add section');

    // Add a new block.
    $this->openAddBlockForm('Powered by Drupal');

    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');

    // Save the new block, and ensure it is displayed on the page.
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->addressEquals($layout_url);
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $this->assertPageNotReloaded();

    // Until the layout is saved, the new block is not visible on the node page.
    $this->drupalGet($node_url);
    $assert_session->pageTextNotContains('Powered by Drupal');

    // When returning to the layout edit mode, the new block is visible.
    $this->drupalGet($layout_url);
    $assert_session->pageTextContains('Powered by Drupal');

    // Save the layout, and the new block is visible.
    $page->pressButton('Save layout');
    $assert_session->addressEquals($node_url);
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $assert_session->elementExists('css', '.layout');

    $this->drupalGet($layout_url);
    $this->markCurrentPage();

    $assert_session->linkExists('Add section');
    $this->clickLink('Add section');
    $this->assertNotEmpty($assert_session->waitForElementVisible('named', ['link', 'Two column']));

    $this->clickLink('Two column');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->assertNoElementAfterWait('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextNotContains('css', '.layout__region--second', 'Powered by Drupal');

    // Drag the block to a region in different section.
    $this->sortableTo('.block-system-powered-by-block', '.layout__region--content', '.layout__region--second');
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure the drag succeeded.
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');

    $this->assertPageNotReloaded();

    // Ensure the dragged block is still in the correct position after reload.
    $this->drupalGet($layout_url);
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');

    // Ensure the dragged block is still in the correct position after save.
    $page->pressButton('Save layout');
    $assert_session->elementExists('css', '.layout__region--second .block-system-powered-by-block');
    $assert_session->elementTextContains('css', '.layout__region--second', 'Powered by Drupal');

    // Reconfigure a block and ensure that the layout content is updated.
    $this->drupalGet($layout_url);
    $this->markCurrentPage();

    $this->clickContextualLink('.block-system-powered-by-block', 'Configure');
    $this->assertOffCanvasFormAfterWait('layout_builder_update_block');

    $page->fillField('settings[label]', 'This is the new label');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $assert_session->addressEquals($layout_url);
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the new label');
    $assert_session->pageTextNotContains('This is the label');

    // Remove a block.
    $this->clickContextualLink('.block-system-powered-by-block', 'Remove block');
    $this->assertOffCanvasFormAfterWait('layout_builder_remove_block');
    $assert_session->pageTextContains('Are you sure you want to remove the This is the new label block?');
    $assert_session->pageTextContains('This action cannot be undone.');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkExists('Add block');
    $assert_session->addressEquals($layout_url);
    $this->assertPageNotReloaded();

    $page->pressButton('Save layout');
    $assert_session->elementExists('css', '.layout');

    // Test deriver-based blocks.
    $this->drupalGet($layout_url);
    $this->markCurrentPage();

    $this->openAddBlockForm('My custom block');
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('This is the block content');

    // Remove both sections.
    $assert_session->linkExists('Remove Section 1');
    $this->clickLink('Remove Section 1');
    $this->assertOffCanvasFormAfterWait('layout_builder_remove_section');
    $assert_session->pageTextContains('Are you sure you want to remove section 1?');
    $assert_session->pageTextContains('This action cannot be undone.');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkExists('Remove Section 1');
    $this->clickLink('Remove Section 1');
    $this->assertOffCanvasFormAfterWait('layout_builder_remove_section');
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('This is the block content');
    $assert_session->linkNotExists('Add block');
    $this->assertPageNotReloaded();

    $page->pressButton('Save layout');

    // Removing all sections results in no layout being used.
    $assert_session->addressEquals($node_url);
    $assert_session->elementNotExists('css', '.layout');
    $assert_session->pageTextNotContains('The node body');
  }

  /**
   * Tests configurable layouts.
   */
  public function testConfigurableLayoutSections() {
    $layout_url = 'node/1/layout';

    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->create([
        'targetEntityType' => 'node',
        'bundle' => 'bundle_with_section_field',
        'mode' => 'full',
      ])
      ->enable()
      ->setThirdPartySetting('layout_builder', 'enabled', TRUE)
      ->setThirdPartySetting('layout_builder', 'allow_custom', TRUE)
      ->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($layout_url);
    $this->markCurrentPage();

    $assert_session->linkExists('Add section');
    $this->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#drupal-off-canvas');

    $assert_session->linkExists('One column');
    $this->clickLink('One column');
    $assert_session->assertWaitOnAjaxRequest();

    // Add another section.
    $assert_session->linkExists('Add section');
    $this->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#drupal-off-canvas');

    $assert_session->linkExists('Layout plugin (with settings)');
    $this->clickLink('Layout plugin (with settings)');
    $this->assertOffCanvasFormAfterWait('layout_builder_configure_section');
    $assert_session->fieldExists('layout_settings[setting_1]');
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->pageTextContains('Default');
    $assert_session->linkExists('Add block');

    // Configure the existing section.
    $assert_session->linkExists('Configure Section 1');
    $this->clickLink('Configure Section 1');
    $this->assertOffCanvasFormAfterWait('layout_builder_configure_section');
    $page->fillField('layout_settings[setting_1]', 'Test setting value');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->pageTextContains('Test setting value');
    $this->assertPageNotReloaded();
  }

  /**
   * Tests bypassing the off-canvas dialog.
   */
  public function testLayoutNoDialog() {
    $layout_url = 'node/1/layout';

    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->create([
        'targetEntityType' => 'node',
        'bundle' => 'bundle_with_section_field',
        'mode' => 'full',
      ])
      ->enable()
      ->setThirdPartySetting('layout_builder', 'enabled', TRUE)
      ->setThirdPartySetting('layout_builder', 'allow_custom', TRUE)
      ->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set up a layout with one section.
    $this->drupalGet(Url::fromRoute('layout_builder.choose_section', [
      'section_storage_type' => 'overrides',
      'section_storage' => 'node.1',
      'delta' => 0,
    ]));
    $assert_session->linkExists('One column');
    $this->clickLink('One column');
    $page->pressButton('Add section');

    // Add a block.
    $this->drupalGet(Url::fromRoute('layout_builder.add_block', [
      'section_storage_type' => 'overrides',
      'section_storage' => 'node.1',
      'delta' => 0,
      'region' => 'content',
      'plugin_id' => 'system_powered_by_block',
    ]));
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $page->fillField('settings[label]', 'The block label');
    $page->fillField('settings[label_display]', TRUE);
    $page->pressButton('Add block');

    $assert_session->addressEquals($layout_url);
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('The block label');

    // Remove the section.
    $this->drupalGet(Url::fromRoute('layout_builder.remove_section', [
      'section_storage_type' => 'overrides',
      'section_storage' => 'node.1',
      'delta' => 0,
    ]));
    $page->pressButton('Remove');
    $assert_session->addressEquals($layout_url);
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('The block label');
    $assert_session->linkNotExists('Add block');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this in https://www.drupal.org/project/drupal/issues/2918718.
   */
  protected function clickContextualLink($selector, $link_locator, $force_visible = TRUE) {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $page->waitFor(10, function () use ($page, $selector) {
      return $page->find('css', "$selector .contextual-links");
    });
    if (count($page->findAll('css', "$selector .contextual-links")) > 1) {
      throw new \Exception('More than one contextual links found by selector');
    }

    if ($force_visible && $page->find('css', "$selector .contextual .trigger.visually-hidden")) {
      $this->toggleContextualTriggerVisibility($selector);
    }

    $link = $assert_session->elementExists('css', $selector)->findLink($link_locator);
    $this->assertNotEmpty($link);

    if (!$link->isVisible()) {
      $button = $assert_session->waitForElementVisible('css', "$selector .contextual button");
      $this->assertNotEmpty($button);
      $button->press();
      $link = $page->waitFor(10, function () use ($link) {
        return $link->isVisible() ? $link : FALSE;
      });
    }

    $link->click();

    if ($force_visible) {
      $this->toggleContextualTriggerVisibility($selector);
    }
  }

  /**
   * Enable layouts.
   *
   * @param string $path
   *   The path for the manage display page.
   * @param bool $allow_custom
   *   Whether to allow custom layouts.
   */
  private function enableLayoutsForBundle($path, $allow_custom = FALSE) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($path);
    $page->checkField('layout[enabled]');
    if ($allow_custom) {
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[name="layout[allow_custom]"]'));
      $page->checkField('layout[allow_custom]');
    }
    $page->pressButton('Save');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#edit-manage-layout'));
    $assert_session->linkExists('Manage layout');
  }

  /**
   * Opens the add block form in the off-canvas dialog.
   *
   * @param string $block_title
   *   The block title which will be the link text.
   */
  private function openAddBlockForm($block_title) {
    $assert_session = $this->assertSession();
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('named', ['link', $block_title]));
    $this->clickLink($block_title);
    $this->assertOffCanvasFormAfterWait('layout_builder_add_block');
  }

  /**
   * Waits for the specified form and returns it when available and visible.
   *
   * @param string $expected_form_id
   *   The expected form ID.
   */
  private function assertOffCanvasFormAfterWait($expected_form_id) {
    $this->assertSession()->assertWaitOnAjaxRequest();
    $off_canvas = $this->assertSession()->waitForElementVisible('css', '#drupal-off-canvas');
    $this->assertNotNull($off_canvas);
    $form_id_element = $off_canvas->find('hidden_field_selector', ['hidden_field', 'form_id']);
    // Ensure the form ID has the correct value and that the form is visible.
    $this->assertNotEmpty($form_id_element);
    $this->assertSame($expected_form_id, $form_id_element->getValue());
    $this->assertTrue($form_id_element->getParent()->isVisible());
  }

  /**
   * Marks the page to assist determining if the page has been reloaded.
   *
   * @todo Remove in https://www.drupal.org/project/drupal/issues/2909782.
   */
  private function markCurrentPage() {
    $this->pageReloadMarker = $this->randomMachineName();
    $this->getSession()->executeScript('document.body.appendChild(document.createTextNode("' . $this->pageReloadMarker . '"));');
  }

  /**
   * Asserts that the page has not been reloaded.
   *
   * @todo Remove in https://www.drupal.org/project/drupal/issues/2909782.
   */
  private function assertPageNotReloaded() {
    $this->assertSession()->pageTextContains($this->pageReloadMarker);
  }

}
