<?php

namespace Drupal\tour\Tests;

use Drupal\simpletest\WebTestBase;

@trigger_error('\Drupal\tour\Tests\TourTestBase is deprecated in 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\tour\Functional\TourTestBase.', E_USER_DEPRECATED);

/**
 * Base class for testing Tour functionality.
 *
 * @deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Tests\tour\Functional\TourTestBase instead.
 */
abstract class TourTestBase extends WebTestBase {

  /**
   * Assert function to determine if tips rendered to the page
   * have a corresponding page element.
   *
   * @param array $tips
   *   A list of tips which provide either a "data-id" or "data-class".
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
   */
  public function assertTourTips($tips = []) {
    // Get the rendered tips and their data-id and data-class attributes.
    if (empty($tips)) {
      // Tips are rendered as <li> elements inside <ol id="tour">.
      $rendered_tips = $this->xpath('//ol[@id = "tour"]//li[starts-with(@class, "tip")]');
      foreach ($rendered_tips as $rendered_tip) {
        $attributes = (array) $rendered_tip->attributes();
        $tips[] = $attributes['@attributes'];
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
          $elements = \PHPUnit_Util_XML::cssSelect('#' . $tip['data-id'], TRUE, $this->content, TRUE);
          $this->assertTrue(!empty($elements) && count($elements) === 1, format_string('Found corresponding page element for tour tip with id #%data-id', ['%data-id' => $tip['data-id']]));
        }
        elseif (!empty($tip['data-class'])) {
          $elements = \PHPUnit_Util_XML::cssSelect('.' . $tip['data-class'], TRUE, $this->content, TRUE);
          $this->assertFalse(empty($elements), format_string('Found corresponding page element for tour tip with class .%data-class', ['%data-class' => $tip['data-class']]));
        }
        else {
          // It's a modal.
          $modals++;
        }
        $total++;
      }
      $this->pass(format_string('Total %total Tips tested of which %modals modal(s).', ['%total' => $total, '%modals' => $modals]));
    }
  }

}
