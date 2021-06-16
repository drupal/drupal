<?php

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Runs tests on Views UI using Claro.
 *
 * @group claro
 */
class ClaroViewsUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Disable automatic live preview to make the sequence of calls clearer.
    $this->config('views.settings')->set('ui.always_live_preview', FALSE)->save();

    // Create the test user and log in.
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'access administration pages',
      'view the administration theme',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests Views UI display menu tabs CSS classes.
   *
   * Ensures that the CSS classes added to display menu tabs are preserved when
   * Views UI is updated with AJAX.
   */
  public function testViewsUiTabsCssClasses() {
    $this->drupalGet('admin/structure/views/view/who_s_online');
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '#views-display-menu-tabs.views-tabs.views-tabs--secondary');
    // Click on the Display name and wait for the Views UI dialog.
    $assert_session->elementExists('css', '#edit-display-settings-top .views-display-setting a')->click();
    $this->assertNotNull($this->assertSession()->waitForElement('css', '.js-views-ui-dialog'));
    // Click the Apply button of the dialog.
    $assert_session->elementExists('css', '.js-views-ui-dialog .ui-dialog-buttonpane')->findButton('Apply')->press();
    // Wait for AJAX to finish.
    $assert_session->assertWaitOnAjaxRequest();

    // Check that the display menu tabs list still has the expected CSS classes.
    $assert_session->elementExists('css', '#views-display-menu-tabs.views-tabs.views-tabs--secondary');
  }

  /**
   * Tests Views UI dropbutton CSS classes.
   *
   * Ensures that the CSS classes added to the Views UI extra actions dropbutton
   * in .views-display-top are preserved when Views UI is refreshed with AJAX.
   */
  public function testViewsUiDropButtonCssClasses() {
    $this->drupalGet('admin/structure/views/view/who_s_online');
    $assert_session = $this->assertSession();
    $extra_actions_dropbutton_list = $assert_session->elementExists('css', '#views-display-extra-actions.dropbutton--small');
    $list_item_selectors = ['li:first-child', 'li:last-child'];
    // Test list item CSS classes.
    foreach ($list_item_selectors as $list_item_selector) {
      $this->assertNotNull($extra_actions_dropbutton_list->find('css', "$list_item_selector.dropbutton__item"));
    }

    // Click on the Display name and wait for the Views UI dialog.
    $assert_session->elementExists('css', '#edit-display-settings-top .views-display-setting a')->click();
    $this->assertNotNull($this->assertSession()->waitForElement('css', '.js-views-ui-dialog'));
    // Click the Apply button of the dialog.
    $assert_session->elementExists('css', '.js-views-ui-dialog .ui-dialog-buttonpane')->findButton('Apply')->press();
    // Wait for AJAX to finish.
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that the drop button list still has the expected CSS classes.
    $this->assertTrue($extra_actions_dropbutton_list->hasClass('dropbutton--small'));
    // Check list item CSS classes.
    foreach ($list_item_selectors as $list_item_selector) {
      $this->assertNotNull($extra_actions_dropbutton_list->find('css', "$list_item_selector.dropbutton__item"));
    }
  }

}
