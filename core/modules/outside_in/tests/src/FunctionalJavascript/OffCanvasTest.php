<?php

namespace Drupal\Tests\outside_in\FunctionalJavascript;

/**
 * Tests the off-canvas tray functionality.
 *
 * @group outside_in
 */
class OffCanvasTest extends OutsideInJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'system', 'toolbar', 'outside_in', 'offcanvas_test'];

  /**
   * Tests that regular non-contextual links will work with the off-canvas tray.
   */
  public function testOffCanvasLinks() {
    $themes = ['bartik', 'stark'];
    // Test the same functionality on multiple themes.
    foreach ($themes as $theme) {
      $this->enableTheme($theme);
      $this->drupalGet('/offcanvas-test-links');

      $page = $this->getSession()->getPage();
      $web_assert = $this->assertSession();

      // Make sure off-canvas tray is on page when first loaded.
      $web_assert->elementNotExists('css', '#drupal-offcanvas');

      // Check opening and closing with two separate links.
      // Make sure tray updates to new content.
      // Check the first link again to make sure the empty title class is
      // removed.
      foreach (['1', '2', '1'] as $link_index) {
        // Click the first test like that should open the page.
        $page->clickLink("Click Me $link_index!");
        $this->waitForOffCanvasToOpen();

        // Check that the canvas is not on the page.
        $web_assert->elementExists('css', '#drupal-offcanvas');
        // Check that response text is on page.
        $web_assert->pageTextContains("Thing $link_index says hello");
        $offcanvas_tray = $this->getTray();

        // Check that tray is visible.
        $this->assertEquals(TRUE, $offcanvas_tray->isVisible());
        $header_text = $offcanvas_tray->find('css', '.ui-dialog-title')->getText();

        $tray_text = $offcanvas_tray->findById('drupal-offcanvas')->getText();
        $this->assertEquals("Thing $link_index says hello", $tray_text);

        if ($link_index == '2') {
          // Check no title behavior.
          $web_assert->elementExists('css', '.ui-dialog-empty-title');
          $this->assertEquals('', $header_text);

          $style = $page->find('css', '.ui-dialog-offcanvas')->getAttribute('style');
          self::assertTrue(strstr($style, 'width: 555px;') !== FALSE, 'Dialog width respected.');
        }
        else {
          // Check that header is correct.
          $this->assertEquals("Thing $link_index", $header_text);
          $web_assert->elementNotExists('css', '.ui-dialog-empty-title');
        }
      }
    }
  }

}
