<?php

namespace Drupal\Tests\system\FunctionalJavascript;

/**
 * Tests the off-canvas dialog functionality.
 *
 * @group system
 */
class OffCanvasTest extends OffCanvasTestBase {

  /**
   * Stores to the class that should be on the last dialog.
   *
   * @var string
   *
   * @see \Drupal\off_canvas_test\Controller\TestController::linksDisplay.
   */
  protected $lastDialogClass;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'off_canvas_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that non-contextual links will work with the off-canvas dialog.
   *
   * @dataProvider themeDataProvider
   */
  public function testOffCanvasLinks($theme) {
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
      $this->assertOffCanvasDialog($link_index, 'side');
      $header_text = $this->getOffCanvasDialog()->find('css', '.ui-dialog-title')->getText();
      if ($link_index == '2') {
        // Check no title behavior.
        $web_assert->elementExists('css', '.ui-dialog-empty-title');
        $this->assertEquals(' ', $header_text);

        $style = $page->find('css', '.ui-dialog-off-canvas')->getAttribute('style');
        $this->assertTrue(strstr($style, 'width: 555px;') !== FALSE, 'Dialog width respected.');
        $page->clickLink("Open side panel 1");
        $this->waitForOffCanvasToOpen();
        $style = $page->find('css', '.ui-dialog-off-canvas')->getAttribute('style');
        $this->assertTrue(strstr($style, 'width: 555px;') === FALSE, 'Dialog width reset to default.');
      }
      else {
        // Check that header is correct.
        $this->assertEquals("Thing $link_index", $header_text);
        $web_assert->elementNotExists('css', '.ui-dialog-empty-title');
      }
    }

    // Test the off_canvas_top tray.
    foreach ([1, 2] as $link_index) {
      $this->assertOffCanvasDialog($link_index, 'top');

      $style = $page->find('css', '.ui-dialog-off-canvas')->getAttribute('style');
      if ($link_index === 1) {
        $this->assertTrue((bool) strstr($style, 'height: auto;'));
      }
      else {
        $this->assertTrue((bool) strstr($style, 'height: 421px;'));
      }
    }

    // Ensure an off-canvas link opened from inside the off-canvas dialog will
    // work.
    $this->drupalGet('/off-canvas-test-links');
    $page->clickLink('Display more links!');
    $this->waitForOffCanvasToOpen();
    $web_assert->linkExists('Off_canvas link!');
    // Click off-canvas link inside off-canvas dialog
    $page->clickLink('Off_canvas link!');
    /*  @var \Behat\Mink\Element\NodeElement $dialog */
    $this->waitForOffCanvasToOpen();
    $web_assert->elementTextContains('css', '.ui-dialog[aria-describedby="drupal-off-canvas"]', 'Thing 2 says hello');

    // Ensure an off-canvas link opened from inside the off-canvas dialog will
    // work after another dialog has been opened.
    $this->drupalGet('/off-canvas-test-links');
    $page->clickLink("Open side panel 1");
    $this->waitForOffCanvasToOpen();
    $page->clickLink('Display more links!');
    $this->waitForOffCanvasToOpen();
    $web_assert->linkExists('Off_canvas link!');
    // Click off-canvas link inside off-canvas dialog
    $page->clickLink('Off_canvas link!');
    /*  @var \Behat\Mink\Element\NodeElement $dialog */
    $this->waitForOffCanvasToOpen();
    $web_assert->elementTextContains('css', '.ui-dialog[aria-describedby="drupal-off-canvas"]', 'Thing 2 says hello');
  }

  /**
   * Tests the body displacement behaves differently at a narrow width.
   */
  public function testNarrowWidth() {
    $narrow_width_breakpoint = 768;
    $offset = 20;
    $height = 800;
    $page = $this->getSession()->getPage();
    $web_assert = $this->assertSession();

    // Test the same functionality on multiple themes.
    foreach ($this->getTestThemes() as $theme) {
      $this->enableTheme($theme);
      // Testing at the wider width.
      $this->getSession()->resizeWindow($narrow_width_breakpoint + $offset, $height);
      $this->drupalGet('/off-canvas-test-links');
      $this->assertFalse($page->find('css', '.dialog-off-canvas-main-canvas')->hasAttribute('style'), 'Body not padded on wide page load.');
      $page->clickLink("Open side panel 1");
      $this->waitForOffCanvasToOpen();
      // Check that the main canvas is padded when page is not narrow width and
      // tray is open.
      $page->waitFor(10, function ($page) {
        return $page->find('css', '.dialog-off-canvas-main-canvas')->hasAttribute('style');
      });
      $web_assert->elementAttributeContains('css', '.dialog-off-canvas-main-canvas', 'style', 'padding-right');

      // Testing at the narrower width.
      $this->getSession()->resizeWindow($narrow_width_breakpoint - $offset, $height);
      $this->drupalGet('/off-canvas-test-links');
      $this->assertFalse($page->find('css', '.dialog-off-canvas-main-canvas')->hasAttribute('style'), 'Body not padded on narrow page load.');
      $page->clickLink("Open side panel 1");
      $this->waitForOffCanvasToOpen();
      $this->assertFalse($page->find('css', '.dialog-off-canvas-main-canvas')->hasAttribute('style'), 'Body not padded on narrow page with tray open.');
    }
  }

  /**
   * @param int $link_index
   *   The index of the link to test.
   * @param string $position
   *   The position of the dialog to test.
   */
  protected function assertOffCanvasDialog($link_index, $position) {
    $page = $this->getSession()->getPage();
    $web_assert = $this->assertSession();
    $link_text = "Open $position panel $link_index";

    // Click the first test like that should open the page.
    $page->clickLink($link_text);
    if ($this->lastDialogClass) {
      $web_assert->assertNoElementAfterWait('css', '.' . $this->lastDialogClass);
    }
    $this->waitForOffCanvasToOpen($position);
    $this->lastDialogClass = "$position-$link_index";

    // Check that response text is on page.
    $web_assert->pageTextContains("Thing $link_index says hello");
    $off_canvas_tray = $this->getOffCanvasDialog();

    // Check that tray is visible.
    $this->assertEquals(TRUE, $off_canvas_tray->isVisible());

    $tray_text = $off_canvas_tray->findById('drupal-off-canvas')->getText();
    $this->assertEquals("Thing $link_index says hello", $tray_text);
  }

}
