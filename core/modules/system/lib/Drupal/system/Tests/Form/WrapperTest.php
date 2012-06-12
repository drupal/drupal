<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\WrapperTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Test wrapper form callbacks.
 */
class WrapperTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Form wrapper callback',
      'description' => 'Tests form wrapper callbacks to pass a prebuilt form to form builder functions.',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp('form_test');
  }

  /**
   * Tests using the form in a usual way.
   */
  function testWrapperCallback() {
    $this->drupalGet('form_test/wrapper-callback');
    $this->assertText('Form wrapper callback element output.', t('The form contains form wrapper elements.'));
    $this->assertText('Form builder element output.', t('The form contains form builder elements.'));
  }
}
