<?php

namespace Drupal\Tests\settings_tray\FunctionalJavascript;

use Drupal\settings_tray_test\Plugin\Block\SettingsTrayFormAnnotationIsClassBlock;
use Drupal\settings_tray_test\Plugin\Block\SettingsTrayFormAnnotationNoneBlock;
use Drupal\user\Entity\Role;

/**
 * Testing opening and saving block forms in the off-canvas dialog.
 *
 * @group #slow
 * @group settings_tray
 */
class SettingsTrayBlockFormTest extends SettingsTrayTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'search',
    'settings_tray_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
      'administer nodes',
      'search content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests opening off-canvas dialog by click blocks and elements in the blocks.
   */
  public function testBlocks() {
    foreach ($this->getBlockTests() as $test) {
      call_user_func_array([$this, 'doTestBlocks'], $test);
    }
  }

  /**
   * Tests opening off-canvas dialog by click blocks and elements in the blocks.
   */
  protected function doTestBlocks($theme, $block_plugin, $new_page_text, $element_selector, $label_selector, $button_text, $toolbar_item, $permissions) {
    if ($permissions) {
      $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), $permissions);
    }
    if ($new_page_text) {
      // Some asserts can be based on this value, so it should not be the same
      // for different blocks, because it can be saved in the site config.
      $new_page_text = $new_page_text . ' ' . $theme . ' ' . $block_plugin;
    }
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->enableTheme($theme);
    $block = $this->placeBlock($block_plugin);
    $block_selector = $this->getBlockSelector($block);
    $block_id = $block->id();
    $this->drupalGet('user');

    $link = $web_assert->waitForElement('css', "$block_selector .contextual-links li a");
    $this->assertEquals('Quick edit', $link->getHtml(), "'Quick edit' is the first contextual link for the block.");
    $destination = (string) $this->loggedInUser->toUrl()->toString();
    $this->assertContains("/admin/structure/block/manage/$block_id/settings-tray?destination=$destination", $link->getAttribute('href'));

    if (isset($toolbar_item)) {
      // Check that you can open a toolbar tray and it will be closed after
      // entering edit mode.
      if ($element = $page->find('css', "#toolbar-administration a.is-active")) {
        // If a tray was open from page load close it.
        $element->click();
        $web_assert->assertNoElementAfterWait('css', "#toolbar-administration a.is-active");
      }
      $page->find('css', $toolbar_item)->click();
      $this->assertElementVisibleAfterWait('css', "{$toolbar_item}.is-active");
    }
    $this->enableEditMode();
    if (isset($toolbar_item)) {
      $web_assert->assertNoElementAfterWait('css', "{$toolbar_item}.is-active");
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
    $this->getSession()->executeScript("jQuery('[data-quickedit-entity-id]').trigger('mouseleave')");
    $this->getSession()->getPage()->find('css', static::TOOLBAR_EDIT_LINK_SELECTOR)->mouseOver();
    $this->assertEditModeDisabled();
    $this->assertNotEmpty($web_assert->waitForElement('css', '#drupal-live-announce:contains(Exited edit mode)'));
    $web_assert->assertNoElementAfterWait('css', '.contextual-toolbar-tab button:contains(Editing)');
    $web_assert->elementAttributeNotContains('css', '.dialog-off-canvas-main-canvas', 'class', 'js-settings-tray-edit-mode');

    // Clean up test data so each test does not impact the next.
    $block->delete();
    if ($permissions) {
      user_role_revoke_permissions(Role::AUTHENTICATED_ID, $permissions);
    }
  }

  /**
   * Creates tests for ::testBlocks().
   */
  public function getBlockTests() {
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
          NULL,
        ],
        "$theme: block-branding" => [
          'theme' => $theme,
          'block_plugin' => 'system_branding_block',
          'new_page_text' => 'The site that will live a very short life',
          'element_selector' => "a[rel='home']:last-child",
          'label_selector' => "a[rel='home']:last-child",
          'button_text' => 'Save Site branding',
          'toolbar_item' => '#toolbar-item-administration',
          ['administer site configuration'],
        ],
        "$theme: block-search" => [
          'theme' => $theme,
          'block_plugin' => 'search_form_block',
          'new_page_text' => NULL,
          'element_selector' => '#edit-submit',
          'label_selector' => 'h2',
          'button_text' => 'Save Search form',
          'toolbar_item' => NULL,
          NULL,
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
          NULL,
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
          NULL,
        ],
      ];
    }

    return $blocks;
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

}
