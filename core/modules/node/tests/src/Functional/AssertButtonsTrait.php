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
      $i = 0;
      $count = count($buttons);

      // Assert there is no save button.
      $this->assertSession()->buttonNotExists('Save');

      // Dropbutton elements.
      /** @var \Behat\Mink\Element\NodeElement[] $elements */
      $elements = $this->xpath('//div[@class="dropbutton-wrapper"]//input[@type="submit"]');
      $this->assertCount($count, $elements);
      foreach ($elements as $element) {
        $value = $element->getValue() ?: '';
        $this->assertEqual($buttons[$i], $value);
        $i++;
      }
    }
    else {
      // Assert there is a save button.
      $this->assertSession()->buttonExists('Save');
      $this->assertNoRaw('dropbutton-wrapper');
    }
  }

}
