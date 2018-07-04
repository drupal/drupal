<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests AJAX responses.
 *
 * @group Ajax
 */
class AjaxTest extends JavascriptTestBase {

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
    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Current theme: stable');
    $assert->pageTextNotContains('Current theme: seven');
  }

  /**
   * Test that AJAX loaded libraries are not retained between requests.
   *
   * @see https://www.drupal.org/node/2647916
   */
  public function testDrupalSettingsCachingRegression() {
    $this->drupalGet('ajax-test/dialog');
    $assert = $this->assertSession();
    $session = $this->getSession();

    // Insert a fake library into the already loaded library settings.
    $fake_library = 'fakeLibrary/fakeLibrary';
    $session->evaluateScript("drupalSettings.ajaxPageState.libraries = drupalSettings.ajaxPageState.libraries + ',$fake_library';");

    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    // Test that the fake library is set.
    $this->assertContains($fake_library, $libraries);

    // Click on the AJAX link.
    $this->clickLink('Link 8 (ajax)');
    $assert->assertWaitOnAjaxRequest();

    // Test that the fake library is still set after the AJAX call.
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertContains($fake_library, $libraries);

    // Reload the page, this should reset the loaded libraries and remove the
    // fake library.
    $this->drupalGet('ajax-test/dialog');
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertNotContains($fake_library, $libraries);

    // Click on the AJAX link again, and the libraries should still not contain
    // the fake library.
    $this->clickLink('Link 8 (ajax)');
    $assert->assertWaitOnAjaxRequest();
    $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
    $this->assertNotContains($fake_library, $libraries);
  }

}
