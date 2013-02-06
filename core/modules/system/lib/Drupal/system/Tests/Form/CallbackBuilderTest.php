<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\CallbackBuilderTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form builder callbacks.
 */
class CallbackBuilderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Form builder callbacks',
      'description' => 'Tests form builder callbacks.',
      'group' => 'Form API',
    );
  }

  /**
   * Tests using a static method to build a form.
   */
  function testStaticMethodCallback() {
    $this->drupalGet('form-test/callback-builder');
    $this->assertText('The Callbacks::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-callback-builder-form"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used even when it is not the callback function name.');
  }

}
