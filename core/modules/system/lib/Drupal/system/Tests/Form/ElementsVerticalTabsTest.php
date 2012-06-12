<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\ElementsVerticalTabsTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Test the vertical_tabs form element for expected behavior.
 */
class ElementsVerticalTabsTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Vertical tabs form element type test',
      'description' => 'Test the vertical_tabs element for expected behavior',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp('form_test');
  }

  /**
   * Ensures that vertical-tabs.js is included before collapse.js.
   *
   * Otherwise, collapse.js adds "SHOW" or "HIDE" labels to the tabs.
   */
  function testJavaScriptOrdering() {
    $this->drupalGet('form_test/vertical-tabs');
    $position1 = strpos($this->content, 'core/misc/vertical-tabs.js');
    $position2 = strpos($this->content, 'core/misc/collapse.js');
    $this->assertTrue($position1 !== FALSE && $position2 !== FALSE && $position1 < $position2, t('vertical-tabs.js is included before collapse.js'));
  }
}
