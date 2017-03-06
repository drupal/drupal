<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Utility\Xss;
use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_form_alter() and hook_form_FORM_ID_alter().
 *
 * @group Form
 */
class AlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'form_test'];

  /**
   * Tests execution order of hook_form_alter() and hook_form_FORM_ID_alter().
   */
  public function testExecutionOrder() {
    $this->drupalGet('form-test/alter');
    // Ensure that the order is first by module, then for a given module, the
    // id-specific one after the generic one.
    $expected = [
      'block_form_form_test_alter_form_alter() executed.',
      'form_test_form_alter() executed.',
      'form_test_form_form_test_alter_form_alter() executed.',
      'system_form_form_test_alter_form_alter() executed.',
    ];
    $content = preg_replace('/\s+/', ' ', Xss::filter($this->content, []));
    $this->assert(strpos($content, implode(' ', $expected)) !== FALSE, 'Form alter hooks executed in the expected order.');
  }

}
