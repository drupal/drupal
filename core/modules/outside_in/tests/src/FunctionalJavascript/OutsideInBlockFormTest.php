<?php

namespace Drupal\Tests\outside_in\FunctionalJavascript;

use Drupal\user\Entity\Role;

/**
 * Testing opening and saving block forms in the off-canvas tray.
 *
 * @group outside_in
 */
class OutsideInBlockFormTest extends OutsideInJavascriptTestBase {

  const TOOLBAR_EDIT_LINK_SELECTOR = '#toolbar-bar div.contextual-toolbar-tab button';

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
    'outside_in',
    'quickedit',
    'search',
    // Add test module to override CSS pointer-events properties because they
    // cause test failures.
    'outside_in_test_css',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // @todo Ensure that this test class works against bartik and stark:
    //   https://www.drupal.org/node/2784881.
    $this->enableTheme('bartik');
    $user = $this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
      'administer nodes',
      'access in-place editing',
      'search content',
    ]);
    $this->drupalLogin($user);

    $this->placeBlock('system_powered_by_block', ['id' => 'powered']);
    $this->placeBlock('system_branding_block', ['id' => 'branding']);
    $this->placeBlock('search_form_block', ['id' => 'search']);
  }

  /**
   * Tests opening Offcanvas tray by click blocks and elements in the blocks.
   *
   * @dataProvider providerTestBlocks
   */
  public function testBlocks($block_id, $new_page_text, $element_selector, $label_selector, $button_text, $toolbar_item) {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $block_selector = '#' . $block_id;
    $this->drupalGet('user');
    if (isset($toolbar_item)) {
      // Check that you can open a toolbar tray and it will be closed after
      // entering edit mode.
      if ($element = $page->find('css', "#toolbar-administration a.is-active")) {
        // If a tray was open from page load close it.
        $element->click();
        $this->waitForNoElement("#toolbar-administration a.is-active");
      }
      $page->find('css', $toolbar_item)->click();
      $web_assert->waitForElementVisible('css', "{$toolbar_item}.is-active");
    }
    $this->enableEditMode();
    if (isset($toolbar_item)) {
      $this->waitForNoElement("{$toolbar_item}.is-active");
    }

    $this->openBlockForm($block_selector);

    switch ($block_id) {
      case 'block-powered':
        // Fill out form, save the form.
        $page->fillField('settings[label]', $new_page_text);
        $page->checkField('settings[label_display]');
        break;

      case 'block-branding':
        // Fill out form, save the form.
        $page->fillField('settings[site_information][site_name]', $new_page_text);
        break;
    }

    if (isset($new_page_text)) {
      $page->pressButton($button_text);
      // Make sure the changes are present.
      // @todo Use a wait method that will take into account the form submitting
      //   and all JavaScript activity. https://www.drupal.org/node/2837676
      //   The use \Behat\Mink\WebAssert::pageTextContains to check text.
      $this->assertJsCondition('jQuery("' . $block_selector . ' ' . $label_selector . '").html() == "' . $new_page_text . '"');
    }

    $this->openBlockForm($block_selector);

    $this->disableEditMode();
    // Canvas should close when editing module is closed.
    $this->waitForOffCanvasToClose();

    $this->enableEditMode();

    $element_selector = "$block_selector {$element_selector}";
    // Open block form by clicking a element inside the block.
    // This confirms that default action for links and form elements is
    // suppressed.
    $this->openBlockForm($element_selector);

    // Exit edit mode using ESC.
    $web_assert->elementTextContains('css', '.contextual-toolbar-tab button', 'Editing');
    $web_assert->elementAttributeContains('css', '.dialog-offcanvas__main-canvas', 'class', 'js-outside-in-edit-mode');
    // Simulate press the Escape key.
    $this->getSession()->executeScript('jQuery("body").trigger(jQuery.Event("keyup", { keyCode: 27 }));');
    $this->waitForOffCanvasToClose();
    $this->getSession()->wait(100);
    $this->assertEditModeDisabled();
    $web_assert->elementTextContains('css', '#drupal-live-announce', 'Exited edit mode.');
    $web_assert->elementTextNotContains('css', '.contextual-toolbar-tab button', 'Editing');
    $web_assert->elementAttributeNotContains('css', '.dialog-offcanvas__main-canvas', 'class', 'js-outside-in-edit-mode');
  }

  /**
   * Dataprovider for testBlocks().
   */
  public function providerTestBlocks() {
    $blocks = [
      'block-powered' => [
        'id' => 'block-powered',
        'new_page_text' => 'Can you imagine anyone showing the label on this block?',
        'element_selector' => '.content a',
        'label_selector' => 'h2',
        'button_text' => 'Save Powered by Drupal',
        'toolbar_item' => '#toolbar-item-user',
      ],
      'block-branding' => [
        'id' => 'block-branding',
        'new_page_text' => 'The site that will live a very short life.',
        'element_selector' => 'a[rel="home"]:nth-child(2)',
        'label_selector' => '.site-branding__name a',
        'button_text' => 'Save Site branding',
        'toolbar_item' => '#toolbar-item-administration',
      ],
      'block-search' => [
        'id' => 'block-search',
        'new_page_text' => NULL,
        'element_selector' => '#edit-submit',
        'label_selector' => 'h2',
        'button_text' => 'Save Search form',
        'toolbar_item' => NULL,
      ],
    ];
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
    $this->pressToolbarEditButton();
    $this->assertEditModeDisabled();
  }

  /**
   * Asserts that Off-Canvas block form is valid.
   */
  protected function assertOffCanvasBlockFormIsValid() {
    $web_assert = $this->assertSession();
    // Check that common block form elements exist.
    $web_assert->elementExists('css', 'input[data-drupal-selector="edit-settings-label"]');
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
   */
  protected function openBlockForm($block_selector) {
    $this->click($block_selector);
    $this->waitForOffCanvasToOpen();
    $this->assertOffCanvasBlockFormIsValid();
  }

  /**
   * Tests QuickEdit links behavior.
   */
  public function testQuickEditLinks() {
    $quick_edit_selector = '#quickedit-entity-toolbar';
    $body_selector = '.field--name-body p';
    $block_selector = '#block-powered';
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
    // Load the same page twice.
    foreach ([1, 2] as $page_load_times) {
      $this->drupalGet('node/' . $node->id());
      // Waiting for Toolbar module.
      // @todo Remove the hack after https://www.drupal.org/node/2542050.
      $web_assert->waitForElementVisible('css', '.toolbar-fixed');
      // Waiting for Toolbar animation.
      $web_assert->assertWaitOnAjaxRequest();
      // The 2nd page load we should already be in edit mode.
      if ($page_load_times == 1) {
        $this->enableEditMode();
      }
      // In Edit mode clicking field should open QuickEdit toolbar.
      $page->find('css', $body_selector)->click();
      $web_assert->waitForElementVisible('css', $quick_edit_selector);

      $this->disableEditMode();
      // Exiting Edit mode should close QuickEdit toolbar.
      $web_assert->elementNotExists('css', $quick_edit_selector);
      // When not in Edit mode QuickEdit toolbar should not open.
      $page->find('css', $body_selector)->click();
      $web_assert->elementNotExists('css', $quick_edit_selector);

      $this->enableEditMode();
      $this->openBlockForm($block_selector);
      $page->find('css', $body_selector)->click();
      $web_assert->waitForElementVisible('css', $quick_edit_selector);
      // Offcanvas should be closed when opening QuickEdit toolbar.
      $this->waitForOffCanvasToClose();

      $this->openBlockForm($block_selector);
      // QuickEdit toolbar should be closed when opening Offcanvas.
      $web_assert->elementNotExists('css', $quick_edit_selector);
    }

    // Check using contextual links to invoke QuickEdit and open the tray.
    $this->drupalGet('node/' . $node->id());
    $web_assert->assertWaitOnAjaxRequest();
    $this->disableEditMode();
    // Open QuickEdit toolbar before going into Edit mode.
    $this->clickContextualLink('.node', "Quick edit");
    $web_assert->waitForElementVisible('css', $quick_edit_selector);
    // Open off-canvas and enter Edit mode via contextual link.
    $this->clickContextualLink($block_selector, "Quick edit");
    $this->waitForOffCanvasToOpen();
    // QuickEdit toolbar should be closed when opening Offcanvas.
    $web_assert->elementNotExists('css', $quick_edit_selector);
    // Open QuickEdit toolbar via contextual link while in Edit mode.
    $this->clickContextualLink('.node', "Quick edit", FALSE);
    $this->waitForOffCanvasToClose();
    $web_assert->waitForElementVisible('css', $quick_edit_selector);
  }

  /**
   * Tests enabling and disabling Edit Mode.
   */
  public function testEditModeEnableDisable() {
    foreach (['contextual_link', 'toolbar_link'] as $enable_option) {
      $this->drupalGet('user');
      $this->assertEditModeDisabled();
      switch ($enable_option) {
        // Enable Edit mode.
        case 'contextual_link':
          $this->clickContextualLink('#block-powered', "Quick edit");
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

  /**
   * Assert that edit mode has been properly enabled.
   */
  protected function assertEditModeEnabled() {
    $web_assert = $this->assertSession();
    // No contextual triggers should be hidden.
    $web_assert->elementNotExists('css', '.contextual .trigger.visually-hidden');
    // The toolbar edit button should read "Editing".
    $web_assert->elementContains('css', static::TOOLBAR_EDIT_LINK_SELECTOR, 'Editing');
    // The main canvas element should have the "js-outside-in-edit-mode" class.
    $web_assert->elementExists('css', '.dialog-offcanvas__main-canvas.js-outside-in-edit-mode');
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
    // The main canvas element should NOT have the "js-outside-in-edit-mode"
    // class.
    $web_assert->elementNotExists('css', '.dialog-offcanvas__main-canvas.js-outside-in-edit-mode');
  }

  /**
   * Press the toolbar Edit button provided by the contextual module.
   */
  protected function pressToolbarEditButton() {
    $this->assertSession()
      ->waitForElementVisible('css', 'div[data-contextual-id="block:block=powered:langcode=en|outside_in::langcode=en"] .contextual-links a', 10000);
    // Waiting for QuickEdit icon animation.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $edit_button = $this->getSession()
      ->getPage()
      ->find('css', static::TOOLBAR_EDIT_LINK_SELECTOR);

    $edit_button->press();
    // Waiting for Toolbar animation.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
