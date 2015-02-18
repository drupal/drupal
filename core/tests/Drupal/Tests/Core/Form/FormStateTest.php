<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormStateTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormState
 *
 * @group Form
 */
class FormStateTest extends UnitTestCase {

  /**
   * Tests the getRedirect() method.
   *
   * @covers ::getRedirect
   *
   * @dataProvider providerTestGetRedirect
   */
  public function testGetRedirect($form_state_additions, $expected) {
    $form_state = (new FormState())->setFormState($form_state_additions);
    $redirect = $form_state->getRedirect();
    $this->assertEquals($expected, $redirect);
  }

  /**
   * Provides test data for testing the getRedirect() method.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestGetRedirect() {
    $data = array();
    $data[] = array(array(), NULL);

    $redirect = new RedirectResponse('/example');
    $data[] = array(array('redirect' => $redirect), $redirect);

    $data[] = array(array('redirect' => new Url('test_route_b', array('key' => 'value'))), new Url('test_route_b', array('key' => 'value')));

    $data[] = array(array('programmed' => TRUE), NULL);
    $data[] = array(array('rebuild' => TRUE), NULL);
    $data[] = array(array('no_redirect' => TRUE), NULL);

    return $data;
  }

  /**
   * Tests the setError() method.
   *
   * @covers ::setError
   */
  public function testSetError() {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('drupalSetMessage')
      ->willReturn('Fail');

    $element['#parents'] = array('foo', 'bar');
    $form_state->setError($element, 'Fail');
  }

  /**
   * Tests the getError() method.
   *
   * @covers ::getError
   *
   * @dataProvider providerTestGetError
   */
  public function testGetError($errors, $parents, $error = NULL) {
    $element['#parents'] = $parents;
    $form_state = (new FormState())->setFormState([
      'errors' => $errors,
    ]);
    $this->assertSame($error, $form_state->getError($element));
  }

  public function providerTestGetError() {
    return array(
      array(array(), array('foo')),
      array(array('foo][bar' => 'Fail'), array()),
      array(array('foo][bar' => 'Fail'), array('foo')),
      array(array('foo][bar' => 'Fail'), array('bar')),
      array(array('foo][bar' => 'Fail'), array('baz')),
      array(array('foo][bar' => 'Fail'), array('foo', 'bar'), 'Fail'),
      array(array('foo][bar' => 'Fail'), array('foo', 'bar', 'baz'), 'Fail'),
      array(array('foo][bar' => 'Fail 2'), array('foo')),
      array(array('foo' => 'Fail 1', 'foo][bar' => 'Fail 2'), array('foo'), 'Fail 1'),
      array(array('foo' => 'Fail 1', 'foo][bar' => 'Fail 2'), array('foo', 'bar'), 'Fail 1'),
    );
  }

  /**
   * @covers ::setErrorByName
   *
   * @dataProvider providerTestSetErrorByName
   */
  public function testSetErrorByName($limit_validation_errors, $expected_errors, $set_message = FALSE) {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->setLimitValidationErrors($limit_validation_errors);
    $form_state->clearErrors();
    $form_state->expects($set_message ? $this->once() : $this->never())
      ->method('drupalSetMessage');

    $form_state->setErrorByName('test', 'Fail 1');
    $form_state->setErrorByName('test', 'Fail 2');
    $form_state->setErrorByName('options');

    $this->assertSame(!empty($expected_errors), $form_state::hasAnyErrors());
    $this->assertSame($expected_errors, $form_state->getErrors());
  }

  public function providerTestSetErrorByName() {
    return array(
      // Only validate the 'options' element.
      array(array(array('options')), array('options' => '')),
      // Do not limit an validation, and, ensuring the first error is returned
      // for the 'test' element.
      array(NULL, array('test' => 'Fail 1', 'options' => ''), TRUE),
      // Limit all validation.
      array(array(), array()),
    );
  }

  /**
   * Tests that form errors during submission throw an exception.
   *
   * @covers ::setErrorByName
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage Form errors cannot be set after form validation has finished.
   */
  public function testFormErrorsDuringSubmission() {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->setValidationComplete();
    $form_state->setErrorByName('test', 'message');
  }

  /**
   * Tests that setting the value for an element adds to the values.
   *
   * @covers ::setValueForElement
   */
  public function testSetValueForElement() {
    $element = array(
      '#parents' => array(
        'foo',
        'bar',
      ),
    );
    $value = $this->randomMachineName();

    $form_state = new FormState();
    $form_state->setValueForElement($element, $value);
    $expected = array(
      'foo' => array(
        'bar' => $value,
      ),
    );
    $this->assertSame($expected, $form_state->getValues());
  }

  /**
   * @covers ::getValue
   *
   * @dataProvider providerTestGetValue
   */
  public function testGetValue($key, $expected, $default = NULL) {
    $form_state = (new FormState())->setValues([
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
    ]);
    $this->assertSame($expected, $form_state->getValue($key, $default));
  }

  public function providerTestGetValue() {
    $data = array();
    $data[] = array(
      'foo', 'one',
    );
    $data[] = array(
      array('bar', 'baz'), 'two',
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), NULL,
    );
    $data[] = array(
      'baz', 'baz', 'baz',
    );
    return $data;
  }

  /**
   * @covers ::setValue
   *
   * @dataProvider providerTestSetValue
   */
  public function testSetValue($key, $value, $expected) {
    $form_state = (new FormState())->setValues([
      'bar' => 'wrong',
    ]);
    $form_state->setValue($key, $value);
    $this->assertSame($expected, $form_state->getValues());
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackValidMethod() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('::buildForm');
    $this->assertEquals([$form_state->getFormObject(), 'buildForm'], $processed_callback);
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackInValidMethod() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('not_a_method');
    // The callback was not changed as no such method exists.
    $this->assertEquals('not_a_method', $processed_callback);
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackArray() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $callback = [$form_state->getFormObject(), 'buildForm'];
    $processed_callback = $form_state->prepareCallback($callback);
    $this->assertEquals($callback, $processed_callback);
  }

  public function providerTestSetValue() {
    $data = array();
    $data[] = array(
      'foo', 'one', array('bar' => 'wrong', 'foo' => 'one'),
    );
    $data[] = array(
      array('bar', 'baz'), 'two', array('bar' => array('baz' => 'two')),
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), NULL, array('bar' => 'wrong', 'foo' => array('bar' => array('baz' => NULL))),
    );
    return $data;
  }

  /**
   * @covers ::hasValue
   *
   * @dataProvider providerTestHasValue
   */
  public function testHasValue($key, $expected) {
    $form_state = (new FormState())->setValues([
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    ]);
    $this->assertSame($expected, $form_state->hasValue($key));
  }

  public function providerTestHasValue() {
    $data = array();
    $data[] = array(
      'foo', TRUE,
    );
    $data[] = array(
      array('bar', 'baz'), TRUE,
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), FALSE,
    );
    $data[] = array(
      'true', TRUE,
    );
    $data[] = array(
      'false', TRUE,
    );
    $data[] = array(
      'null', FALSE,
    );
    return $data;
  }

  /**
   * @covers ::isValueEmpty
   *
   * @dataProvider providerTestIsValueEmpty
   */
  public function testIsValueEmpty($key, $expected) {
    $form_state = (new FormState())->setValues([
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    ]);
    $this->assertSame($expected, $form_state->isValueEmpty($key));
  }

  public function providerTestIsValueEmpty() {
    $data = array();
    $data[] = array(
      'foo', FALSE,
    );
    $data[] = array(
      array('bar', 'baz'), FALSE,
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), TRUE,
    );
    $data[] = array(
      'true', FALSE,
    );
    $data[] = array(
      'false', TRUE,
    );
    $data[] = array(
      'null', TRUE,
    );
    return $data;
  }

  /**
   * @covers ::loadInclude
   */
  public function testLoadInclude() {
    $type = 'some_type';
    $module = 'some_module';
    $name = 'some_name';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('moduleLoadInclude'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $name)
      ->willReturn(TRUE);
    $this->assertTrue($form_state->loadInclude($module, $type, $name));
  }

  /**
   * @covers ::loadInclude
   */
  public function testLoadIncludeNoName() {
    $type = 'some_type';
    $module = 'some_module';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('moduleLoadInclude'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $module)
      ->willReturn(TRUE);
    $this->assertTrue($form_state->loadInclude($module, $type));
  }

  /**
   * @covers ::loadInclude
   */
  public function testLoadIncludeNotFound() {
    $type = 'some_type';
    $module = 'some_module';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('moduleLoadInclude'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $module)
      ->willReturn(FALSE);
    $this->assertFalse($form_state->loadInclude($module, $type));
  }

  /**
   * @covers ::loadInclude
   */
  public function testLoadIncludeAlreadyLoaded() {
    $type = 'some_type';
    $module = 'some_module';
    $name = 'some_name';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('moduleLoadInclude'))
      ->getMock();

    $form_state->addBuildInfo('files', [
      'some_module:some_name.some_type' => [
        'type' => $type,
        'module' => $module,
        'name' => $name,
      ],
    ]);
    $form_state->expects($this->never())
      ->method('moduleLoadInclude');

    $this->assertFalse($form_state->loadInclude($module, $type, $name));
  }

  /**
   * @covers ::isCached
   *
   * @dataProvider providerTestIsCached
   */
  public function testIsCached($cache_key, $no_cache_key, $expected) {
    $form_state = (new FormState())->setFormState([
      'cache' => $cache_key,
      'no_cache' => $no_cache_key,
    ]);
    $this->assertSame($expected, $form_state->isCached());
  }

  /**
   * Provides test data for testIsCached().
   */
  public function providerTestIsCached() {
    $data = [];
    $data[] = [
      TRUE,
      TRUE,
      FALSE,
    ];
    $data[] = [
      FALSE,
      TRUE,
      FALSE,
    ];
    $data[] = [
      FALSE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      TRUE,
      FALSE,
      TRUE,
    ];
    $data[] = [
      TRUE,
      NULL,
      TRUE,
    ];
    $data[] = [
      FALSE,
      NULL,
      FALSE,
    ];
    return $data;
  }

  /**
   * @covers ::isMethodType
   * @covers ::setMethod
   *
   * @dataProvider providerTestIsMethodType
   */
  public function testIsMethodType($set_method_type, $input, $expected) {
    $form_state = (new FormState())
      ->setMethod($set_method_type);
    $this->assertSame($expected, $form_state->isMethodType($input));
  }

  /**
   * Provides test data for testIsMethodType().
   */
  public function providerTestIsMethodType() {
    $data = [];
    $data[] = [
      'get',
      'get',
      TRUE,
    ];
    $data[] = [
      'get',
      'GET',
      TRUE,
    ];
    $data[] = [
      'GET',
      'GET',
      TRUE,
    ];
    $data[] = [
      'post',
      'get',
      FALSE,
    ];
    return $data;
  }

  /**
   * @covers ::getTemporaryValue
   * @covers ::hasTemporaryValue
   * @covers ::setTemporaryValue
   */
  public function testTemporaryValue() {
    $form_state = New FormState();
    $this->assertFalse($form_state->hasTemporaryValue('rainbow_sparkles'));
    $form_state->setTemporaryValue('rainbow_sparkles', 'yes please');
    $this->assertSame($form_state->getTemporaryValue('rainbow_sparkles'), 'yes please');
    $this->assertTrue($form_state->hasTemporaryValue('rainbow_sparkles'), TRUE);
    $form_state->setTemporaryValue(array('rainbow_sparkles', 'magic_ponies'), 'yes please');
    $this->assertSame($form_state->getTemporaryValue(array('rainbow_sparkles', 'magic_ponies')), 'yes please');
    $this->assertTrue($form_state->hasTemporaryValue(array('rainbow_sparkles', 'magic_ponies')), TRUE);
  }

  /**
   * @covers ::getCleanValueKeys
   */
  public function testGetCleanValueKeys() {
    $form_state = new FormState();
    $this->assertSame($form_state->getCleanValueKeys(), ['form_id', 'form_token', 'form_build_id', 'op']);
  }

  /**
   * @covers ::setCleanValueKeys
   */
  public function testSetCleanValueKeys() {
    $form_state = new FormState();
    $form_state->setCleanValueKeys(['key1', 'key2']);
    $this->assertSame($form_state->getCleanValueKeys(), ['key1', 'key2']);
  }

  /**
   * @covers ::addCleanValueKey
   */
  public function testAddCleanValueKey() {
    $form_state = new FormState();
    $form_state->setValue('value_to_clean', 'rainbow_sprinkles');
    $form_state->addCleanValueKey('value_to_clean');
    $this->assertSame($form_state->getCleanValueKeys(), ['form_id', 'form_token', 'form_build_id', 'op', 'value_to_clean']);
    return $form_state;
  }

  /**
   * @depends testAddCleanValueKey
   *
   * @covers ::cleanValues
   */
  public function testCleanValues($form_state) {
    $form_state->setValue('value_to_keep', 'magic_ponies');
    $form_state->cleanValues();
    $this->assertSame($form_state->getValues(), ['value_to_keep' => 'magic_ponies']);
  }
}

/**
 * A test form used for the prepareCallback() tests.
 */
class PrepareCallbackTestForm implements FormInterface {
  public function getFormId() {
    return 'test_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {}
  public function validateForm(array &$form, FormStateInterface $form_state) { }
  public function submitForm(array &$form, FormStateInterface $form_state) { }
}
