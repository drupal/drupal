<?php

namespace Drupal\node\Tests;

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
  public function assertButtons($buttons, $dropbutton = TRUE) {

    // Try to find a Save button.
    $save_button = $this->xpath('//input[@type="submit"][@value="Save"]');

    // Verify that the number of buttons passed as parameters is
    // available in the dropbutton widget.
    if ($dropbutton) {
      $i = 0;
      $count = count($buttons);

      // Assert there is no save button.
      $this->assertTrue(empty($save_button));

      // Dropbutton elements.
      $elements = $this->xpath('//div[@class="dropbutton-wrapper"]//input[@type="submit"]');
      $this->assertEqual($count, count($elements));
      foreach ($elements as $element) {
        $value = isset($element['value']) ? (string) $element['value'] : '';
        $this->assertEqual($buttons[$i], $value);
        $i++;
      }
    }
    else {
      // Assert there is a save button.
      $this->assertTrue(!empty($save_button));
      $this->assertNoRaw('dropbutton-wrapper');
    }
  }
}
