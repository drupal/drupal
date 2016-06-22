<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests that AJAX responses use the current theme.
 *
 * @group Ajax
 */
class AjaxThemeTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_test'];

  public function testAjaxWithAdminRoute() {
    \Drupal::service('theme_installer')->install(['stable', 'seven']);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('admin', 'seven');
    $theme_config->set('default', 'stable');
    $theme_config->save();

    $account = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($account);

    // First visit the site directly via the URL. This should render it in the
    // admin theme.
    $this->drupalGet('admin/ajax-test/theme');
    $assert = $this->assertSession();
    $assert->pageTextContains('Current theme: seven');

    // Now click the modal, which should also use the admin theme.
    $this->drupalGet('ajax-test/dialog');
    $assert->pageTextNotContains('Current theme: stable');
    $this->clickLink('Link 8 (ajax)');
    $this->waitForAjaxToFinish();

    $assert->pageTextContains('Current theme: stable');
    $assert->pageTextNotContains('Current theme: seven');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
