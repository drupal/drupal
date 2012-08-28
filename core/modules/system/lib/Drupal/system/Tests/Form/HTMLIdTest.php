<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\HTMLIdTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests uniqueness of generated HTML IDs.
 */
class HTMLIdTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Unique HTML IDs',
      'description' => 'Tests functionality of drupal_html_id().',
      'group' => 'Form API',
    );
  }

  /**
   * Tests that HTML IDs do not get duplicated when form validation fails.
   */
  function testHTMLId() {
    $this->drupalGet('form-test/double-form');
    $this->assertNoDuplicateIds('There are no duplicate IDs');

    // Submit second form with empty title.
    $edit = array();
    $this->drupalPost(NULL, $edit, 'Save', array(), array(), 'form-test-html-id--2');
    $this->assertNoDuplicateIds('There are no duplicate IDs');
  }

}
