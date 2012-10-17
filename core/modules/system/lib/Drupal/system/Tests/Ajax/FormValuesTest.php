<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\FormValuesTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Tests that $form_state['values'] is properly delivered to $ajax['callback'].
 */
class FormValuesTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'AJAX command form values',
      'description' => 'Tests that form values are properly delivered to AJAX callbacks.',
      'group' => 'AJAX',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Submits forms with select and checkbox elements via Ajax.
   */
  function testSimpleAjaxFormValue() {
    // Verify form values of a select element.
    foreach (array('red', 'green', 'blue') as $item) {
      $edit = array(
        'select' => $item,
      );
      $commands = $this->drupalPostAJAX('ajax_forms_test_get_form', $edit, 'select');
      $expected = array(
        'command' => 'data',
        'value' => $item,
      );
      $this->assertCommand($commands, $expected, "verification of AJAX form values from a selectbox issued with a correct value");
    }

    // Verify form values of a checkbox element.
    foreach (array(FALSE, TRUE) as $item) {
      $edit = array(
        'checkbox' => $item,
      );
      $commands = $this->drupalPostAJAX('ajax_forms_test_get_form', $edit, 'checkbox');
      $expected = array(
        'command' => 'data',
        'value' => (int) $item,
      );
      $this->assertCommand($commands, $expected, "verification of AJAX form values from a checkbox issued with a correct value");
    }
  }
}
