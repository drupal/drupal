<?php

namespace Drupal\Tests\node\Functional;

/**
 * Asserts that buttons are present on a page.
 */
trait AssertButtonsTrait {

  /**
   * Assert method to verify the buttons in the dropdown element.
   *
   * @param array $buttons
   *   A collection of buttons to assert for on the page.
   * @param bool $dropbutton
   *   Whether to check if the buttons are in a dropbutton widget or not.
   */
  public function assertButtons(array $buttons, $dropbutton = TRUE) {
    // Verify that the number of buttons passed as parameters is
    // available in the dropbutton widget.
    if ($dropbutton) {
      $count = count($buttons);

      // Assert there is no save button.
      $this->assertSession()->buttonNotExists('Save');

      // Dropbutton elements.
      $this->assertSession()->elementsCount('xpath', '//div[@class="dropbutton-wrapper"]//input[@type="submit"]', $count);
      for ($i = 1; $i++; $i <= $count) {
        $this->assertSession()->elementTextEquals('xpath', "(//div[@class='dropbutton-wrapper']//input[@type='submit'])[$i]", $buttons[$i - 1]);
      }
    }
    else {
      // Assert there is a save button.
      $this->assertSession()->buttonExists('Save');
      $this->assertSession()->responseNotContains('dropbutton-wrapper');
    }
  }

}
