<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\AlterTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Test form alter hooks.
 */
class AlterTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Form alter hooks',
      'description' => 'Tests hook_form_alter() and hook_form_FORM_ID_alter().',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp(array('block', 'form_test'));
  }

  /**
   * Tests execution order of hook_form_alter() and hook_form_FORM_ID_alter().
   */
  function testExecutionOrder() {
    $this->drupalGet('form-test/alter');
    // Ensure that the order is first by module, then for a given module, the
    // id-specific one after the generic one.
    $expected = array(
      'block_form_form_test_alter_form_alter() executed.',
      'form_test_form_alter() executed.',
      'form_test_form_form_test_alter_form_alter() executed.',
      'system_form_form_test_alter_form_alter() executed.',
    );
    $content = preg_replace('/\s+/', ' ', filter_xss($this->content, array()));
    $this->assert(strpos($content, implode(' ', $expected)) !== FALSE, t('Form alter hooks executed in the expected order.'));
  }
}
