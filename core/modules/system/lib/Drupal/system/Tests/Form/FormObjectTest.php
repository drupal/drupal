<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\FormObjectTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\system\Tests\System\SystemConfigFormBase;
use Drupal\form_test\FormTestObject;

/**
 * Tests building a form from an object.
 */
class FormObjectTest extends SystemConfigFormBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Form object tests',
      'description' => 'Tests building a form from an object.',
      'group' => 'Form API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->form_id = new FormTestObject();
    $this->values = array(
      'bananas' => array(
        '#value' => $this->randomString(10),
        '#config_name' => 'form_test.object',
        '#config_key' => 'bananas',
      ),
    );
  }

  /**
   * Tests using an object as the form callback.
   */
  function testObjectFormCallback() {
    $this->drupalGet('form-test/object-builder');
    $this->assertText('The FormTestObject::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-form-test-object"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used.');
    $this->drupalPost('form-test/object-builder', NULL, t('Save'));
    $this->assertText('The FormTestObject::validateForm() method was used for this form.');
    $this->assertText('The FormTestObject::submitForm() method was used for this form.');
  }

}
