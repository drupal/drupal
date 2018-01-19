<?php

namespace Drupal\Tests\settings_tray\FunctionalJavascript;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\settings_tray_test\Plugin\Block\SettingsTrayFormAnnotationIsClassBlock;
use Drupal\settings_tray_test\Plugin\Block\SettingsTrayFormAnnotationNoneBlock;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\system\FunctionalJavascript\OffCanvasTestBase;
use Drupal\user\Entity\Role;

/**
 * Testing opening and saving block forms in the off-canvas dialog.
 *
 * @group settings_tray
 */
class SettingsTrayBlockFormTest extends OffCanvasTestBase {

  use ContextualLinkClickTrait;

  const TOOLBAR_EDIT_LINK_SELECTOR = '#toolbar-bar div.contextual-toolbar-tab button';

  const LABEL_INPUT_SELECTOR = 'input[data-drupal-selector="edit-settings-label"]';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'block',
    'system',
    'breakpoint',
    'toolbar',
    'contextual',
    'settings_tray',
    'quickedit',
    'search',
    'block_content',
    'settings_tray_test',
    // Add test module to override CSS pointer-events properties because they
    // cause test failures.
    'settings_tray_test_css',
    'settings_tray_test',
    'settings_tray_override_test',
    'menu_ui',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createBlockContentType('basic', TRUE);
    $block_content = $this->createBlockContent('Custom Block', 'basic', TRUE);
    $user = $this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
      'administer nodes',
      'access in-place editing',
      'search content',
    ]);
    $this->drupalLogin($user);
    $this->placeBlock('block_content:' . $block_content->uuid(), ['id' => 'custom']);
  }

  /**
   * Tests opening off-canvas dialog by click blocks and elements in the blocks.
   *
   * @dataProvider providerTestBlocks
   */
  public function testBlocks($theme, $block_plugin, $new_page_text, $element_selector, $label_selector, $button_text, $toolbar_item) {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->enableTheme($theme);
    $block = $this->placeBlock($block_plugin);
    $block_selector = $this->getBlockSelector($block);
    $block_id = $block->id();
    $this->drupalGet('user');

    $link = $page->find('css', "$block_selector .contextual-links li a");
    $this->assertEquals('Quick edit', $link->getText(), "'Quick edit' is the first contextual link for the block.");
    $this->assertContains("/admin/structure/block/manage/$block_id/off-canvas?destination=user/2", $link->getAttribute('href'));

    if (isset($toolbar_item)) {
      // Check that you can open a toolbar tray and it will be closed after
      // entering edit mode.
      if ($element = $page->find('css', "#toolbar-administration a.is-active")) {
        // If a tray was open from page load close it.
        $element->click();
        $this->waitForNoElement("#toolbar-administration a.is-active");
      }
      $page->find('css', $toolbar_item)->click();
      $this->assertElementVisibleAfterWait('css', "{$toolbar_item}.is-active");
    }
    $this->enableEditMode();
    if (isset($toolbar_item)) {
      $this->waitForNoElement("{$toolbar_item}.is-active");
    }
    $this->openBlockForm($block_selector);
    switch ($block_plugin) {
      case 'system_powered_by_block':
        // Confirm "Display Title" is not checked.
        $web_assert->checkboxNotChecked('settings[label_display]');
        // Confirm Title is not visible.
        $this->assertEquals($this->isLabelInputVisible(), FALSE, 'Label is not visible');
        $page->checkField('settings[label_display]');
        $this->assertEquals($this->isLabelInputVisible(), TRUE, 'Label is visible');
        // Fill out form, save the form.
        $page->fillField('settings[label]', $new_page_text);

        break;

      case 'system_branding_block':
        // Fill out form, save the form.
        $page->fillField('settings[site_information][site_name]', $new_page_text);
        break;

      case 'settings_tray_test_class':
        $web_assert->elementExists('css', '[data-drupal-selector="edit-settings-some-setting"]');
        break;
    }

    if (isset($new_page_text)) {
      $page->pressButton($button_text);
      // Make sure the changes are present.
      $new_page_text_locator = "$block_selector $label_selector:contains($new_page_text)";
      $this->assertElementVisibleAfterWait('css', $new_page_text_locator);
      // The page is loaded with the new change but make sure page is
      // completely loaded.
      $this->assertPageLoadComplete();
    }

    $this->openBlockForm($block_selector);

    $this->disableEditMode();
    // Canvas should close when editing module is closed.
    $this->waitForOffCanvasToClose();

    $this->enableEditMode();

    // Open block form by clicking a element inside the block.
    // This confirms that default action for links and form elements is
    // suppressed.
    $this->openBlockForm("$block_selector {$element_selector}", $block_selector);
    $web_assert->elementTextContains('css', '.contextual-toolbar-tab button', 'Editing');
    $web_assert->elementAttributeContains('css', '.dialog-off-canvas-main-canvas', 'class', 'js-settings-tray-edit-mode');
    // Simulate press the Escape key.
    $this->getSession()->executeScript('jQuery("body").trigger(jQuery.Event("keyup", { keyCode: 27 }));');
    $this->waitForOffCanvasToClose();
    $this->getSession()->wait(100);
    $this->assertEditModeDisabled();
    $web_assert->elementTextContains('css', '#drupal-live-announce', 'Exited edit mode.');
    $web_assert->elementTextNotContains('css', '.contextual-toolbar-tab button', 'Editing');
    $web_assert->elementAttributeNotContains('css', '.dialog-off-canvas-main-canvas', 'class', 'js-settings-tray-edit-mode');
  }

  /**
   * Dataprovider for testBlocks().
   */
  public function providerTestBlocks() {
    $blocks = [];
    foreach ($this->getTestThemes() as $theme) {
      $blocks += [
        "$theme: block-powered" => [
          'theme' => $theme,
          'block_plugin' => 'system_powered_by_block',
          'new_page_text' => 'Can you imagine anyone showing the label on this block',
          'element_selector' => 'span a',
          'label_selector' => 'h2',
          'button_text' => 'Save Powered by Drupal',
          'toolbar_item' => '#toolbar-item-user',
        ],
        "$theme: block-branding" => [
          'theme' => $theme,
          'block_plugin' => 'system_branding_block',
          'new_page_text' => 'The site that will live a very short life',
          'element_selector' => "a[rel='home']:last-child",
          'label_selector' => "a[rel='home']:last-child",
          'button_text' => 'Save Site branding',
          'toolbar_item' => '#toolbar-item-administration',
        ],
        "$theme: block-search" => [
          'theme' => $theme,
          'block_plugin' => 'search_form_block',
          'new_page_text' => NULL,
          'element_selector' => '#edit-submit',
          'label_selector' => 'h2',
          'button_text' => 'Save Search form',
          'toolbar_item' => NULL,
        ],
        // This is the functional JS test coverage accompanying
        // \Drupal\Tests\settings_tray\Functional\SettingsTrayTest::testPossibleAnnotations().
        "$theme: " . SettingsTrayFormAnnotationIsClassBlock::class => [
          'theme' => $theme,
          'block_plugin' => 'settings_tray_test_class',
          'new_page_text' => NULL,
          'element_selector' => 'span',
          'label_selector' => NULL,
          'button_text' => NULL,
          'toolbar_item' => NULL,
        ],
        // This is the functional JS test coverage accompanying
        // \Drupal\Tests\settings_tray\Functional\SettingsTrayTest::testPossibleAnnotations().
        "$theme: " . SettingsTrayFormAnnotationNoneBlock::class => [
          'theme' => $theme,
          'block_plugin' => 'settings_tray_test_none',
          'new_page_text' => NULL,
          'element_selector' => 'span',
          'label_selector' => NULL,
          'button_text' => NULL,
          'toolbar_item' => NULL,
        ],
      ];
    }

    return $blocks;
  }

  /**
   * Enables edit mode by pressing edit button in the toolbar.
   */
  protected function enableEditMode() {
    $this->pressToolbarEditButton();
    $this->assertEditModeEnabled();
  }

  /**
   * Disables edit mode by pressing edit button in the toolbar.
   */
  protected function disableEditMode() {
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->pressToolbarEditButton();
    $this->assertEditModeDisabled();
  }

  /**
   * Asserts that Off-Canvas block form is valid.
   */
  protected function assertOffCanvasBlockFormIsValid() {
    $web_assert = $this->assertSession();
    // Confirm that Block title display label has been changed.
    $web_assert->elementTextContains('css', '.form-item-settings-label-display label', 'Display block title');
    // Confirm Block title label is shown if checkbox is checked.
    if ($this->getSession()->getPage()->find('css', 'input[name="settings[label_display]"]')->isChecked()) {
      $this->assertEquals($this->isLabelInputVisible(), TRUE, 'Label is visible');
      $web_assert->elementTextContains('css', '.form-item-settings-label label', 'Block title');
    }
    else {
      $this->assertEquals($this->isLabelInputVisible(), FALSE, 'Label is not visible');
    }

    // Check that common block form elements exist.
    $web_assert->elementExists('css', static::LABEL_INPUT_SELECTOR);
    $web_assert->elementExists('css', 'input[data-drupal-selector="edit-settings-label-display"]');
    // Check that advanced block form elements do not exist.
    $web_assert->elementNotExists('css', 'input[data-drupal-selector="edit-visibility-request-path-pages"]');
    $web_assert->elementNotExists('css', 'select[data-drupal-selector="edit-region"]');
  }

  /**
   * Open block form by clicking the element found with a css selector.
   *
   * @param string $block_selector
   *   A css selector selects the block or an element within it.
   * @param string $contextual_link_container
   *   The element that contains the contextual links. If none provide the
   *   $block_selector will be used.
   */
  protected function openBlockForm($block_selector, $contextual_link_container = '') {
    if (!$contextual_link_container) {
      $contextual_link_container = $block_selector;
    }
    // Ensure that contextual link element is present because this is required
    // to open the off-canvas dialog in edit mode.
    $contextual_link = $this->assertSession()->waitForElement('css', "$contextual_link_container .contextual-links a");
    $this->assertNotEmpty($contextual_link);
    // When page first loads Edit Mode is not triggered until first contextual
    // link is added.
    $this->assertElementVisibleAfterWait('css', '.dialog-off-canvas-main-canvas.js-settings-tray-edit-mode');
    // Ensure that all other Ajax activity is completed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->click($block_selector);
    $this->waitForOffCanvasToOpen();
    $this->assertOffCanvasBlockFormIsValid();
  }

  /**
   * Tests QuickEdit links behavior.
   */
  public function testQuickEditLinks() {
    $quick_edit_selector = '#quickedit-entity-toolbar';
    $node_selector = '[data-quickedit-entity-id="node/1"]';
    $body_selector = '[data-quickedit-field-id="node/1/body/en/full"]';
    $web_assert = $this->assertSession();
    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $auth_role = Role::load(Role::AUTHENTICATED_ID);
    $this->grantPermissions($auth_role, [
      'edit any page content',
      'access content',
    ]);
    $node = $this->createNode(
      [
        'title' => 'Page One',
        'type' => 'page',
        'body' => [
          [
            'value' => 'Regular NODE body for the test.',
            'format' => 'plain_text',
          ],
        ],
      ]
    );
    $page = $this->getSession()->getPage();
    $block_plugin = 'system_powered_by_block';

    foreach ($this->getTestThemes() as $theme) {

      $this->enableTheme($theme);

      $block = $this->placeBlock($block_plugin);
      $block_selector = $this->getBlockSelector($block);
      // Load the same page twice.
      foreach ([1, 2] as $page_load_times) {
        $this->drupalGet('node/' . $node->id());
        // The 2nd page load we should already be in edit mode.
        if ($page_load_times == 1) {
          $this->enableEditMode();
        }
        // In Edit mode clicking field should open QuickEdit toolbar.
        $page->find('css', $body_selector)->click();
        $this->assertElementVisibleAfterWait('css', $quick_edit_selector);

        $this->disableEditMode();
        // Exiting Edit mode should close QuickEdit toolbar.
        $web_assert->elementNotExists('css', $quick_edit_selector);
        // When not in Edit mode QuickEdit toolbar should not open.
        $page->find('css', $body_selector)->click();
        $web_assert->elementNotExists('css', $quick_edit_selector);
        $this->enableEditMode();
        $this->openBlockForm($block_selector);
        $page->find('css', $body_selector)->click();
        $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
        // Off-canvas dialog should be closed when opening QuickEdit toolbar.
        $this->waitForOffCanvasToClose();

        $this->openBlockForm($block_selector);
        // QuickEdit toolbar should be closed when opening Off-canvas dialog.
        $web_assert->elementNotExists('css', $quick_edit_selector);
      }
      // Check using contextual links to invoke QuickEdit and open the tray.
      $this->drupalGet('node/' . $node->id());
      $web_assert->assertWaitOnAjaxRequest();
      $this->disableEditMode();
      // Open QuickEdit toolbar before going into Edit mode.
      $this->clickContextualLink($node_selector, "Quick edit");
      $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
      // Open off-canvas and enter Edit mode via contextual link.
      $this->clickContextualLink($block_selector, "Quick edit");
      $this->waitForOffCanvasToOpen();
      // QuickEdit toolbar should be closed when opening off-canvas dialog.
      $web_assert->elementNotExists('css', $quick_edit_selector);
      // Open QuickEdit toolbar via contextual link while in Edit mode.
      $this->clickContextualLink($node_selector, "Quick edit", FALSE);
      $this->waitForOffCanvasToClose();
      $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
      $this->disableEditMode();
    }
  }

  /**
   * Tests enabling and disabling Edit Mode.
   */
  public function testEditModeEnableDisable() {
    foreach ($this->getTestThemes() as $theme) {
      $this->enableTheme($theme);
      $block = $this->placeBlock('system_powered_by_block');
      foreach (['contextual_link', 'toolbar_link'] as $enable_option) {
        $this->drupalGet('user');
        $this->assertEditModeDisabled();
        switch ($enable_option) {
          // Enable Edit mode.
          case 'contextual_link':
            $this->clickContextualLink($this->getBlockSelector($block), "Quick edit");
            $this->waitForOffCanvasToOpen();
            $this->assertEditModeEnabled();
            break;

          case 'toolbar_link':
            $this->enableEditMode();
            break;
        }
        $this->disableEditMode();

        // Make another page request to ensure Edit mode is still disabled.
        $this->drupalGet('user');
        $this->assertEditModeDisabled();
        // Make sure on this page request it also re-enables and disables
        // correctly.
        $this->enableEditMode();
        $this->disableEditMode();
      }
    }
  }

  /**
   * Assert that edit mode has been properly enabled.
   */
  protected function assertEditModeEnabled() {
    $web_assert = $this->assertSession();
    // No contextual triggers should be hidden.
    $web_assert->elementNotExists('css', '.contextual .trigger.visually-hidden');
    // The toolbar edit button should read "Editing".
    $web_assert->elementContains('css', static::TOOLBAR_EDIT_LINK_SELECTOR, 'Editing');
    // The main canvas element should have the "js-settings-tray-edit-mode" class.
    $web_assert->elementExists('css', '.dialog-off-canvas-main-canvas.js-settings-tray-edit-mode');
  }

  /**
   * Assert that edit mode has been properly disabled.
   */
  protected function assertEditModeDisabled() {
    $web_assert = $this->assertSession();
    // Contextual triggers should be hidden.
    $web_assert->elementExists('css', '.contextual .trigger.visually-hidden');
    // No contextual triggers should be not hidden.
    $web_assert->elementNotExists('css', '.contextual .trigger:not(.visually-hidden)');
    // The toolbar edit button should read "Edit".
    $web_assert->elementContains('css', static::TOOLBAR_EDIT_LINK_SELECTOR, 'Edit');
    // The main canvas element should NOT have the "js-settings-tray-edit-mode"
    // class.
    $web_assert->elementNotExists('css', '.dialog-off-canvas-main-canvas.js-settings-tray-edit-mode');
  }

  /**
   * Press the toolbar Edit button provided by the contextual module.
   */
  protected function pressToolbarEditButton() {
    $this->assertSession()->waitForElement('css', '[data-contextual-id] .contextual-links a');
    $edit_button = $this->getSession()
      ->getPage()
      ->find('css', static::TOOLBAR_EDIT_LINK_SELECTOR);
    $edit_button->press();
  }

  /**
   * Creates a custom block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($label, $create_body = FALSE) {
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

  /**
   * Tests that contextual links in custom blocks are changed.
   *
   * "Quick edit" is quickedit.module link.
   * "Quick edit settings" is settings_tray.module link.
   */
  public function testCustomBlockLinks() {
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $links = $page->findAll('css', "#block-custom .contextual-links li a");
    $link_labels = [];
    /** @var \Behat\Mink\Element\NodeElement $link */
    foreach ($links as $link) {
      $link_labels[$link->getAttribute('href')] = $link->getText();
    }
    $href = array_search('Quick edit', $link_labels);
    $this->assertEquals('', $href);
    $href = array_search('Quick edit settings', $link_labels);
    $this->assertTrue(strstr($href, '/admin/structure/block/manage/custom/off-canvas?destination=user/2') !== FALSE);
  }

  /**
   * Gets the block CSS selector.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block.
   *
   * @return string
   *   The CSS selector.
   */
  public function getBlockSelector(Block $block) {
    return '#block-' . str_replace('_', '-', $block->id());
  }

  /**
   * Determines if the label input is visible.
   *
   * @return bool
   *   TRUE if the label is visible, FALSE if it is not.
   */
  protected function isLabelInputVisible() {
    return $this->getSession()->getPage()->find('css', static::LABEL_INPUT_SELECTOR)->isVisible();
  }

  /**
   * Test that validation errors appear in the off-canvas dialog.
   */
  public function testValidationMessages() {
    $page = $this->getSession()->getPage();
    $web_assert = $this->assertSession();
    foreach ($this->getTestThemes() as $theme) {
      $this->enableTheme($theme);
      $block = $this->placeBlock('settings_tray_test_validation');
      $this->drupalGet('user');
      $this->enableEditMode();
      $this->openBlockForm($this->getBlockSelector($block));
      $page->pressButton('Save Block with validation error');
      $web_assert->assertWaitOnAjaxRequest();
      // The settings_tray_test_validation test plugin form always has a
      // validation error.
      $web_assert->elementContains('css', '#drupal-off-canvas', 'Sorry system error. Please save again');
      $this->disableEditMode();
      $block->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestThemes() {
    // Remove 'seven' theme. Setting Tray "Edit Mode" will not work with 'seven'
    // because it removes all contextual links the off-canvas dialog should.
    return array_filter(parent::getTestThemes(), function ($theme) {
      return $theme !== 'seven';
    });
  }

  /**
   * Tests that blocks with configuration overrides are disabled.
   */
  public function testOverriddenBlock() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $overridden_block = $this->placeBlock('system_powered_by_block', [
      'id' => 'overridden_block',
      'label_display' => 1,
      'label' => 'This will be overridden.',
    ]);
    $this->drupalGet('user');
    $block_selector = $this->getBlockSelector($overridden_block);
    // Confirm the block is marked as Settings Tray editable.
    $this->assertEquals('editable', $page->find('css', $block_selector)->getAttribute('data-drupal-settingstray'));
    // Confirm the label is not overridden.
    $web_assert->elementContains('css', $block_selector, 'This will be overridden.');
    $this->enableEditMode();
    $this->openBlockForm($block_selector);

    // Confirm the block Settings Tray functionality is disabled when block is
    // overridden.
    $this->container->get('state')->set('settings_tray_override_test.block', TRUE);
    $overridden_block->save();
    $block_config = \Drupal::configFactory()->getEditable('block.block.overridden_block');
    $block_config->set('settings', $block_config->get('settings'))->save();

    $this->drupalGet('user');
    $this->assertOverriddenBlockDisabled($overridden_block, 'Now this will be the label.');

    // Test a non-overridden block does show the form in the off-canvas dialog.
    $block = $this->placeBlock('system_powered_by_block', [
      'label_display' => 1,
      'label' => 'Labely label',
    ]);
    $this->drupalGet('user');
    $block_selector = $this->getBlockSelector($block);
    // Confirm the block is marked as Settings Tray editable.
    $this->assertEquals('editable', $page->find('css', $block_selector)->getAttribute('data-drupal-settingstray'));
    // Confirm the label is not overridden.
    $web_assert->elementContains('css', $block_selector, 'Labely label');
    $this->openBlockForm($block_selector);
  }

  /**
   * Test  blocks with overridden related configuration removed when overridden.
   */
  public function testOverriddenConfigurationRemoved() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Confirm the branding block does include 'site_information' section when
    // the site name is not overridden.
    $branding_block = $this->placeBlock('system_branding_block');
    $this->drupalGet('user');
    $this->enableEditMode();
    $this->openBlockForm($this->getBlockSelector($branding_block));
    $web_assert->fieldExists('settings[site_information][site_name]');
    // Confirm the branding block does not include 'site_information' section
    // when the site name is overridden.
    $this->container->get('state')->set('settings_tray_override_test.site_name', TRUE);
    $this->drupalGet('user');
    $this->openBlockForm($this->getBlockSelector($branding_block));
    $web_assert->fieldNotExists('settings[site_information][site_name]');
    $page->pressButton('Save Site branding');
    $this->assertElementVisibleAfterWait('css', 'div:contains(The block configuration has been saved)');
    $web_assert->assertWaitOnAjaxRequest();
    // Confirm we did not save changes to the configuration.
    $this->assertEquals('Llama Fan Club', \Drupal::configFactory()->get('system.site')->get('name'));
    $this->assertEquals('Drupal', \Drupal::configFactory()->getEditable('system.site')->get('name'));

    // Add a link or the menu will not render.
    $menu_link_content = MenuLinkContent::create([
      'title' => 'This is on the menu',
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $menu_link_content->save();
    // Confirm the menu block does include menu section when the menu is not
    // overridden.
    $menu_block = $this->placeBlock('system_menu_block:main');
    $web_assert->assertWaitOnAjaxRequest();
    $this->drupalGet('user');
    $web_assert->pageTextContains('This is on the menu');
    $this->openBlockForm($this->getBlockSelector($menu_block));
    $web_assert->elementExists('css', '#menu-overview');

    // Confirm the menu block does not include menu section when the menu is
    // overridden.
    $this->container->get('state')->set('settings_tray_override_test.menu', TRUE);
    $this->drupalGet('user');
    $web_assert->pageTextContains('This is on the menu');
    $menu_with_overrides = \Drupal::configFactory()->get('system.menu.main')->get();
    $menu_without_overrides = \Drupal::configFactory()->getEditable('system.menu.main')->get();
    $this->openBlockForm($this->getBlockSelector($menu_block));
    $web_assert->elementNotExists('css', '#menu-overview');
    $page->pressButton('Save Main navigation');
    $this->assertElementVisibleAfterWait('css', 'div:contains(The block configuration has been saved)');
    $web_assert->assertWaitOnAjaxRequest();
    // Confirm we did not save changes to the configuration.
    $this->assertEquals('Labely label', \Drupal::configFactory()->get('system.menu.main')->get('label'));
    $this->assertEquals('Main navigation', \Drupal::configFactory()->getEditable('system.menu.main')->get('label'));
    $this->assertEquals($menu_with_overrides, \Drupal::configFactory()->get('system.menu.main')->get());
    $this->assertEquals($menu_without_overrides, \Drupal::configFactory()->getEditable('system.menu.main')->get());
    $web_assert->pageTextContains('This is on the menu');
  }
  /**
   * Asserts that an overridden block has Settings Tray disabled.
   *
   * @param \Drupal\block\Entity\Block $overridden_block
   *   The overridden block.
   * @param string $override_text
   *   The override text that should appear in the block.
   */
  protected function assertOverriddenBlockDisabled(Block $overridden_block, $override_text) {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $block_selector = $this->getBlockSelector($overridden_block);
    $block_id = $overridden_block->id();
    // Confirm the block does not have a quick edit link.
    $contextual_links = $page->findAll('css', "$block_selector .contextual-links li a");
    $this->assertNotEmpty($contextual_links);
    foreach ($contextual_links as $link) {
      $this->assertNotContains("/admin/structure/block/manage/$block_id/off-canvas", $link->getAttribute('href'));
    }
    // Confirm the block is not marked as Settings Tray editable.
    $this->assertFalse($page->find('css', $block_selector)
      ->hasAttribute('data-drupal-settingstray'));

    // Confirm the text is actually overridden.
    $web_assert->elementContains('css', $this->getBlockSelector($overridden_block), $override_text);
  }

}
