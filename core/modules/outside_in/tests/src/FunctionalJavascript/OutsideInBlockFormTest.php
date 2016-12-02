<?php

namespace Drupal\Tests\outside_in\FunctionalJavascript;

/**
 * Testing opening and saving block forms in the off-canvas tray.
 *
 * @group outside_in
 */
class OutsideInBlockFormTest extends OutsideInJavascriptTestBase {

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
   */
  public function testBlocks() {
    // @todo: re-enable once https://www.drupal.org/node/2830485 is resolved.
    $this->markTestSkipped('Test skipped due to random failures in DrupalCI, see https://www.drupal.org/node/2830485');

    $web_assert = $this->assertSession();
    $blocks = [
      [
        'id' => 'block-powered',
        'new_page_text' => 'Can you imagine anyone showing the label on this block?',
        'element_selector' => '.content a',
        'button_text' => 'Save Powered by Drupal',
        'toolbar_item' => '#toolbar-item-user',
      ],
      [
        'id' => 'block-branding',
        'new_page_text' => 'The site that will live a very short life.',
        'element_selector' => 'a[rel="home"]:nth-child(2)',
        'button_text' => 'Save Site branding',
        'toolbar_item' => '#toolbar-item-administration',
      ],
      [
        'id' => 'block-search',
        'element_selector' => '#edit-submit',
        'button_text' => 'Save Search form',
      ],
    ];
    $page = $this->getSession()->getPage();
    foreach ($blocks as $block) {
      $block_selector = '#' . $block['id'];
      $this->drupalGet('user');
      if (isset($block['toolbar_item'])) {
        // Check that you can open a toolbar tray and it will be closed after
        // entering edit mode.
        if ($element = $page->find('css', "#toolbar-administration a.is-active")) {
          // If a tray was open from page load close it.
          $element->click();
          $this->waitForNoElement("#toolbar-administration a.is-active");
        }
        $page->find('css', $block['toolbar_item'])->click();
        $this->waitForElement("{$block['toolbar_item']}.is-active");
      }
      $this->toggleEditingMode();
      if (isset($block['toolbar_item'])) {
        $this->waitForNoElement("{$block['toolbar_item']}.is-active");
      }

      $this->openBlockForm($block_selector);

      switch ($block['id']) {
        case 'block-powered':
          // Fill out form, save the form.
          $page->fillField('settings[label]', $block['new_page_text']);
          $page->checkField('settings[label_display]');
          break;

        case 'block-branding':
          // Fill out form, save the form.
          $page->fillField('settings[site_information][site_name]', $block['new_page_text']);
          break;
      }

      if (isset($block['new_page_text'])) {
        $page->pressButton($block['button_text']);
        // Make sure the changes are present.
        $this->assertSession()->assertWaitOnAjaxRequest();
        $web_assert->pageTextContains($block['new_page_text']);
      }

      $this->openBlockForm($block_selector);

      $this->toggleEditingMode();
      // Canvas should close when editing module is closed.
      $this->waitForOffCanvasToClose();

      // Go into Edit mode again.
      $this->toggleEditingMode();

      $element_selector = "$block_selector {$block['element_selector']}";
      // Open block form by clicking a element inside the block.
      // This confirms that default action for links and form elements is
      // suppressed.
      $this->openBlockForm($element_selector);

      // Exit edit mode.
      $this->toggleEditingMode();
    }
  }

  /**
   * Enables Editing mode by pressing "Edit" button in the toolbar.
   */
  protected function toggleEditingMode() {
    $this->waitForElement('div[data-contextual-id="block:block=powered:langcode=en|outside_in::langcode=en"] .contextual-links a');

    $this->waitForElement('#toolbar-bar');

    $edit_button = $this->getSession()->getPage()->find('css', '#toolbar-bar div.contextual-toolbar-tab button');

    $edit_button->press();
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

}
