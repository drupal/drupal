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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Vertical tabs form element type test',
      'description' => 'Test the vertical_tabs element for expected behavior',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('access vertical_tab_test tabs'));
    $this->web_user = $this->drupalCreateUser();
    $this->drupalLogin($this->admin_user);
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
    $this->assertTrue($position1 !== FALSE && $position2 !== FALSE && $position1 < $position2, 'vertical-tabs.js is included before collapse.js');
  }

  /**
   * Ensures that vertical tab markup is not shown if user has no tab access.
   */
  function testWrapperNotShownWhenEmpty() {
    // Test admin user can see vertical tabs and wrapper.
    $this->drupalGet('form_test/vertical-tabs');
    $wrapper = $this->xpath("//div[@data-vertical-tabs-panes]");
    $this->assertTrue(isset($wrapper[0]), 'Vertical tab panes found.');

    // Test wrapper markup not present for non-privileged web user.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('form_test/vertical-tabs');
    $wrapper = $this->xpath("//div[@data-vertical-tabs-panes]");
    $this->assertFalse(isset($wrapper[0]), 'Vertical tab wrappers are not displayed to unprivileged users.');
  }
}
