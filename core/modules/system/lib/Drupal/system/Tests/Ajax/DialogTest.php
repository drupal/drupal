<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\DialogTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Tests use of dialogs as wrappers for Ajax responses.
 */
class DialogTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Dialog',
      'description' => 'Performs tests on #ajax[\'dialog\'].',
      'group' => 'AJAX',
    );
  }

  /**
   * Ensure elements with #ajax['dialog'] render correctly.
   */
  function testDialog() {
    // Ensure the elements render without notices or exceptions.
    $this->drupalGet('ajax-test/dialog');

    // @todo What else should we assert?
  }

}
