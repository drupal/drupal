<?php

namespace Drupal\system\Tests\Form;

use Drupal\system\Tests\System\SystemConfigFormTestBase;
use Drupal\form_test\FormTestObject;

/**
 * Tests building a form from an object.
 *
 * @group Form
 */
class FormObjectTest extends SystemConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  protected function setUp() {
    parent::setUp();

    $this->form = new FormTestObject($this->container->get('config.factory'));
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
   *
   * @see \Drupal\form_test\EventSubscriber\FormTestEventSubscriber::onKernelRequest()
   */
  function testObjectFormCallback() {
    $config_factory = $this->container->get('config.factory');

    $this->drupalGet('form-test/object-builder');
    $this->assertText('The FormTestObject::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-form-test-object"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used.');
    $this->drupalPostForm(NULL, array('bananas' => 'green'), t('Save'));
    $this->assertText('The FormTestObject::validateForm() method was used for this form.');
    $this->assertText('The FormTestObject::submitForm() method was used for this form.');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertIdentical('green', $value);

    $this->drupalGet('form-test/object-arguments-builder/yellow');
    $this->assertText('The FormTestArgumentsObject::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-form-test-arguments-object"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used.');
    $this->drupalPostForm(NULL, NULL, t('Save'));
    $this->assertText('The FormTestArgumentsObject::validateForm() method was used for this form.');
    $this->assertText('The FormTestArgumentsObject::submitForm() method was used for this form.');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertIdentical('yellow', $value);

    $this->drupalGet('form-test/object-service-builder');
    $this->assertText('The FormTestServiceObject::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-form-test-service-object"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used.');
    $this->drupalPostForm(NULL, array('bananas' => 'brown'), t('Save'));
    $this->assertText('The FormTestServiceObject::validateForm() method was used for this form.');
    $this->assertText('The FormTestServiceObject::submitForm() method was used for this form.');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertIdentical('brown', $value);

    $this->drupalGet('form-test/object-controller-builder');
    $this->assertText('The FormTestControllerObject::create() method was used for this form.');
    $this->assertText('The FormTestControllerObject::buildForm() method was used for this form.');
    $elements = $this->xpath('//form[@id="form-test-form-test-controller-object"]');
    $this->assertTrue(!empty($elements), 'The correct form ID was used.');
    $this->assertText('custom_value', 'Ensure parameters are injected from request attributes.');
    $this->assertText('request_value', 'Ensure the request object is injected.');
    $this->drupalPostForm(NULL, array('bananas' => 'black'), t('Save'));
    $this->assertText('The FormTestControllerObject::validateForm() method was used for this form.');
    $this->assertText('The FormTestControllerObject::submitForm() method was used for this form.');
    $value = $config_factory->get('form_test.object')->get('bananas');
    $this->assertIdentical('black', $value);
  }

}
