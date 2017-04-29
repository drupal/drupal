<?php

namespace Drupal\Tests\outside_in\FunctionalJavascript;

/**
 * Tests the off-canvas dialog functionality.
 *
 * @group outside_in
 */
class OffCanvasTest extends OutsideInJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'system', 'toolbar', 'outside_in', 'off_canvas_test'];

  /**
   * Tests that regular non-contextual links will work with the off-canvas dialog.
   */
  public function testOffCanvasLinks() {
    $themes = ['bartik', 'stark'];
    // Test the same functionality on multiple themes.
    foreach ($themes as $theme) {
      $this->enableTheme($theme);
      $this->drupalGet('/off-canvas-test-links');

      $page = $this->getSession()->getPage();
      $web_assert = $this->assertSession();

      // Make sure off-canvas dialog is on page when first loaded.
      $web_assert->elementNotExists('css', '#drupal-off-canvas');

      // Check opening and closing with two separate links.
      // Make sure tray updates to new content.
      // Check the first link again to make sure the empty title class is
      // removed.
      foreach (['1', '2', '1'] as $link_index) {
        // Click the first test like that should open the page.
        $page->clickLink("Click Me $link_index!");
        $this->waitForOffCanvasToOpen();

        // Check that the canvas is not on the page.
        $web_assert->elementExists('css', '#drupal-off-canvas');
        // Check that response text is on page.
        $web_assert->pageTextContains("Thing $link_index says hello");
        $off_canvas_tray = $this->getTray();

        // Check that tray is visible.
        $this->assertEquals(TRUE, $off_canvas_tray->isVisible());
        $header_text = $off_canvas_tray->find('css', '.ui-dialog-title')->getText();

        $tray_text = $off_canvas_tray->findById('drupal-off-canvas')->getText();
        $this->assertEquals("Thing $link_index says hello", $tray_text);

        if ($link_index == '2') {
          // Check no title behavior.
          $web_assert->elementExists('css', '.ui-dialog-empty-title');
          $this->assertEquals('', $header_text);

          $style = $page->find('css', '.ui-dialog-off-canvas')->getAttribute('style');
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

  /**
   * Tests the body displacement behaves differently at a narrow width.
   */
  public function testNarrowWidth() {
    $themes = ['stark', 'bartik'];
    $narrow_width_breakpoint = 768;
    $offset = 20;
    $height = 800;
    $page = $this->getSession()->getPage();
    $web_assert = $this->assertSession();

    // Test the same functionality on multiple themes.
    foreach ($themes as $theme) {
      $this->enableTheme($theme);
      // Testing at the wider width.
      $this->getSession()->resizeWindow($narrow_width_breakpoint + $offset, $height);
      $this->drupalGet('/off-canvas-test-links');
      $this->assertFalse($page->find('css', '.dialog-off-canvas__main-canvas')->hasAttribute('style'), 'Body not padded on wide page load.');
      $page->clickLink("Click Me 1!");
      $this->waitForOffCanvasToOpen();
      // Check that the main canvas is padded when page is not narrow width and
      // tray is open.
      $web_assert->elementAttributeContains('css', '.dialog-off-canvas__main-canvas', 'style', 'padding-right');

      // Testing at the narrower width.
      $this->getSession()->resizeWindow($narrow_width_breakpoint - $offset, $height);
      $this->drupalGet('/off-canvas-test-links');
      $this->assertFalse($page->find('css', '.dialog-off-canvas__main-canvas')->hasAttribute('style'), 'Body not padded on narrow page load.');
      $page->clickLink("Click Me 1!");
      $this->waitForOffCanvasToOpen();
      $this->assertFalse($page->find('css', '.dialog-off-canvas__main-canvas')->hasAttribute('style'), 'Body not padded on narrow page with tray open.');
    }
  }

}
