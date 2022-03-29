<?php

namespace Drupal\Tests\system\Traits;

/**
 * Provides helper methods for interacting with the off-canvas area.
 *
 * This trait is only useful in functional JavaScript tests which need to use
 * the off-canvas area. Tests using this trait should also list off_canvas_test
 * in their $modules property.
 */
trait OffCanvasTestTrait {

  /**
   * Waits for the off-canvas area to appear, resized and visible.
   */
  protected function waitForOffCanvasArea(): void {
    // The data-resize-done attribute is added by the off_canvas_test module's
    // wrapper around Drupal.offCanvas.resetSize.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '[data-resize-done="true"]'));
  }

}
