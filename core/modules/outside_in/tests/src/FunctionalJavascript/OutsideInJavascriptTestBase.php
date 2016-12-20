<?php

namespace Drupal\Tests\outside_in\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base class contains common test functionality for the Settings Tray module.
 */
abstract class OutsideInJavascriptTestBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    $return = parent::drupalGet($path, $options, $headers);

    // After the page loaded we need to additionally wait until the settings
    // tray Ajax activity is done.
    $this->assertSession()->assertWaitOnAjaxRequest();

    return $return;
  }

  /**
   * Enables a theme.
   *
   * @param string $theme
   *   The theme.
   */
  public function enableTheme($theme) {
    // Enable the theme.
    \Drupal::service('theme_installer')->install([$theme]);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('default', $theme);
    $theme_config->save();
  }

  /**
   * Waits for Off-canvas tray to open.
   */
  protected function waitForOffCanvasToOpen() {
    $web_assert = $this->assertSession();
    $web_assert->assertWaitOnAjaxRequest();
    $this->waitForElement('#drupal-offcanvas');
  }

  /**
   * Waits for Off-canvas tray to close.
   */
  protected function waitForOffCanvasToClose() {
    $this->waitForNoElement('#drupal-offcanvas');
  }

  /**
   * Waits for an element to appear on the page.
   *
   * @param string $selector
   *   CSS selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitForElement($selector, $timeout = 10000) {
    $condition = "(jQuery('$selector').length > 0)";
    $this->assertJsCondition($condition, $timeout);
  }

  /**
   * Gets the Off-Canvas tray element.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   */
  protected function getTray() {
    $tray = $this->getSession()->getPage()->find('css', '.ui-dialog[aria-describedby="drupal-offcanvas"]');
    $this->assertEquals(FALSE, empty($tray), 'The tray was found.');
    return $tray;
  }

  /**
   * Waits for an element to be removed from the page.
   *
   * @param string $selector
   *   CSS selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitForNoElement($selector, $timeout = 10000) {
    $condition = "(jQuery('$selector').length == 0)";
    $this->assertJsCondition($condition, $timeout);
  }

}
