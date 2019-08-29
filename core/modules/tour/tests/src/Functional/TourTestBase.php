<?php

namespace Drupal\Tests\tour\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing Tour functionality.
 */
abstract class TourTestBase extends BrowserTestBase {

  /**
   * Assert function to determine if tips rendered to the page
   * have a corresponding page element.
   *
   * @code
   * // Basic example.
   * $this->assertTourTips();
   *
   * // Advanced example. The following would be used for multipage or
   * // targeting a specific subset of tips.
   * $tips = array();
   * $tips[] = array('data-id' => 'foo');
   * $tips[] = array('data-id' => 'bar');
   * $tips[] = array('data-class' => 'baz');
   * $this->assertTourTips($tips);
   * @endcode
   *
   * @param array $tips
   *   A list of tips which provide either a "data-id" or "data-class".
   */
  public function assertTourTips($tips = []) {
    // Get the rendered tips and their data-id and data-class attributes.
    if (empty($tips)) {
      // Tips are rendered as <li> elements inside <ol id="tour">.
      $rendered_tips = $this->xpath('//ol[@id = "tour"]//li[starts-with(@class, "tip")]');
      foreach ($rendered_tips as $rendered_tip) {
        $tips[] = [
          'data-id' => $rendered_tip->getAttribute('data-id'),
          'data-class' => $rendered_tip->getAttribute('data-class'),
        ];
      }
    }

    // If the tips are still empty we need to fail.
    if (empty($tips)) {
      $this->fail('Could not find tour tips on the current page.');
    }
    else {
      // Check for corresponding page elements.
      $total = 0;
      $modals = 0;
      foreach ($tips as $tip) {
        if (!empty($tip['data-id'])) {
          $elements = $this->getSession()->getPage()->findAll('css', '#' . $tip['data-id']);
          $this->assertTrue(!empty($elements) && count($elements) === 1, new FormattableMarkup('Found corresponding page element for tour tip with id #%data-id', ['%data-id' => $tip['data-id']]));
        }
        elseif (!empty($tip['data-class'])) {
          $elements = $this->getSession()->getPage()->findAll('css', '.' . $tip['data-class']);
          $this->assertFalse(empty($elements), new FormattableMarkup('Found corresponding page element for tour tip with class .%data-class', ['%data-class' => $tip['data-class']]));
        }
        else {
          // It's a modal.
          $modals++;
        }
        $total++;
      }
      $this->pass(new FormattableMarkup('Total %total Tips tested of which %modals modal(s).', ['%total' => $total, '%modals' => $modals]));
    }
  }

}
