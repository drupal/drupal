<?php

declare(strict_types=1);

namespace Drupal\Tests\tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing Tour functionality.
 */
abstract class TourTestBase extends BrowserTestBase {

  /**
   * Asserts the presence of page elements for tour tips.
   *
   * @code
   * // Basic example.
   * $this->assertTourTips();
   *
   * // Advanced example. The following would be used for multi-page or
   * // targeting a specific subset of tips.
   * $tips = [];
   * $tips[] = ['data-id' => 'foo'];
   * $tips[] = ['data-id' => 'bar'];
   * $tips[] = ['data-class' => 'baz'];
   * $this->assertTourTips($tips);
   * @endcode
   *
   * @param array $tips
   *   A list of tips which provide either a "data-id" or "data-class".
   * @param bool $expectEmpty
   *   Whether or not the field is expected to be Empty.
   */
  public function assertTourTips(array $tips = [], bool $expectEmpty = FALSE) {
    // Get the rendered tips and their data-id and data-class attributes.
    if (empty($tips)) {
      // Tips are rendered as drupalSettings values.
      $drupalSettings = $this->getDrupalSettings();
      if (isset($drupalSettings['_tour_internal'])) {
        foreach ($drupalSettings['_tour_internal'] as $tip) {
          $tips[] = [
            'selector' => $tip['selector'] ?? NULL,
          ];
        }
      }
    }

    $tip_count = count($tips);
    if ($tip_count === 0 && $expectEmpty) {
      // No tips found as expected.
      return;
    }
    if ($tip_count > 0 && $expectEmpty) {
      $this->fail("No tips were expected but $tip_count were found");
    }
    $this->assertGreaterThan(0, $tip_count);

    // Check for corresponding page elements.
    $total = 0;
    $modals = 0;
    foreach ($tips as $tip) {
      if (!empty($tip['data-id'])) {
        $elements = $this->getSession()->getPage()->findAll('css', '#' . $tip['data-id']);
        $this->assertCount(1, $elements, sprintf('Found corresponding page element for tour tip with id #%s', $tip['data-id']));
      }
      elseif (!empty($tip['data-class'])) {
        $elements = $this->getSession()->getPage()->findAll('css', '.' . $tip['data-class']);
        $this->assertNotEmpty($elements, sprintf("Page element for tour tip with class .%s should be present", $tip['data-class']));
      }
      else {
        // It's a modal.
        $modals++;
      }
      $total++;
    }
  }

}
