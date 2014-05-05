<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormValidatorTest.
 */

namespace Drupal\Tests\Core\Form {

use Drupal\Component\Utility\String;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the form validator.
 *
 * @coversDefaultClass \Drupal\Core\Form\FormValidator
 *
 * @group Drupal
 * @group Form
 */
class FormValidatorTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Form validator test',
      'description' => 'Tests the form validator.',
      'group' => 'Form API',
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
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $form_state['validation_complete'] = TRUE;
    $form_validator->setErrorByName('test', $form_state, 'message');
  }

  /**
   * Tests the 'validation_complete' $form_state flag.
   *
   * @covers ::validateForm
   * @covers ::finalizeValidation
   */
  public function testValidationComplete() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $form = array();
    $form_state = $this->getFormStateDefaults();
    $this->assertFalse($form_state['validation_complete']);
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state['validation_complete']);
  }

  /**
   * Tests the 'must_validate' $form_state flag.
   *
   * @covers ::validateForm
   */
  public function testPreventDuplicateValidation() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form = array();
    $form_state = $this->getFormStateDefaults();
    $form_state['validation_complete'] = TRUE;
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertArrayNotHasKey('#errors', $form);
  }

  /**
   * Tests the 'must_validate' $form_state flag.
   *
   * @covers ::validateForm
   */
  public function testMustValidate() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('doValidateForm');

    $form = array();
    $form_state = $this->getFormStateDefaults();
    $form_state['validation_complete'] = TRUE;
    $form_state['must_validate'] = TRUE;
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertArrayHasKey('#errors', $form);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateInvalidFormToken() {
    $request_stack = new RequestStack();
    $request = new Request(array(), array(), array(), array(), array(), array('REQUEST_URI' => '/test/example?foo=bar'));
    $request_stack->push($request);
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $csrf_token->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(FALSE));

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($request_stack, $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('setErrorByName', 'doValidateForm'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('setErrorByName')
      ->with('form_token', $this->isType('array'), 'The form has become outdated. Copy any unsaved work in the form below and then <a href="/test/example?foo=bar">reload this page</a>.');
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getFormStateDefaults();
    $form_state['values']['form_token'] = 'some_random_token';
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state['validation_complete']);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateValidFormToken() {
    $request_stack = new RequestStack();
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $csrf_token->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(TRUE));

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($request_stack, $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('setErrorByName', 'doValidateForm'))
      ->getMock();
    $form_validator->expects($this->never())
      ->method('setErrorByName');
    $form_validator->expects($this->once())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getFormStateDefaults();
    $form_state['values']['form_token'] = 'some_random_token';
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state['validation_complete']);
  }

  /**
   * Tests the setError() method.
   *
   * @covers ::setError
   */
  public function testSetError() {
    $form_state = $this->getFormStateDefaults();

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(array('setErrorByName'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('setErrorByName')
      ->with('foo][bar', $form_state, 'Fail');

    $element['#parents'] = array('foo', 'bar');
    $form_validator->setError($element, $form_state, 'Fail');
  }

  /**
   * Tests the getError() method.
   *
   * @covers ::getError
   *
   * @dataProvider providerTestGetError
   */
  public function testGetError($errors, $parents, $error = NULL) {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $element['#parents'] = $parents;
    $form_state = $this->getFormStateDefaults();
    $form_state['errors'] = $errors;
    $this->assertSame($error, $form_validator->getError($element, $form_state));
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
    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($request_stack, $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_validator->expects($set_message ? $this->once() : $this->never())
      ->method('drupalSetMessage');

    $form_state = $this->getFormStateDefaults();
    $form_state['limit_validation_errors'] = $limit_validation_errors;
    $form_validator->setErrorByName('test', $form_state, 'Fail 1');
    $form_validator->setErrorByName('test', $form_state, 'Fail 2');
    $form_validator->setErrorByName('options', $form_state);

    $this->assertSame(!empty($expected_errors), $request->attributes->get('_form_errors', FALSE));
    $this->assertSame($expected_errors, $form_state['errors']);
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
   * @covers ::setElementErrorsFromFormState
   */
  public function testSetElementErrorsFromFormState() {
    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array($request_stack, $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();

    $form = array(
      '#parents' => array(),
    );
    $form['test'] = array(
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => array('test'),
    );
    $form_state = $this->getFormStateDefaults();
    $form_validator->setErrorByName('test', $form_state, 'invalid');
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertSame('invalid', $form['test']['#errors']);
  }

  /**
   * @covers ::handleErrorsWithLimitedValidation
   *
   * @dataProvider providerTestHandleErrorsWithLimitedValidation
   */
  public function testHandleErrorsWithLimitedValidation($sections, $triggering_element, $values, $expected) {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $form = array();
    $form_state = $this->getFormStateDefaults();
    $form_state['triggering_element'] = $triggering_element;
    $form_state['triggering_element']['#limit_validation_errors'] = $sections;

    $form_state['values'] = $values;
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertSame($expected, $form_state['values']);
  }

  public function providerTestHandleErrorsWithLimitedValidation() {
    return array(
      // Test with a non-existent section.
      array(
        array(array('test1'), array('test3')),
        array(),
        array(
          'test1' => 'foo',
          'test2' => 'bar',
        ),
        array(
          'test1' => 'foo',
        ),
      ),
      // Test with buttons in a non-validated section.
      array(
        array(array('test1')),
        array(
          '#is_button' => true,
          '#value' => 'baz',
          '#name' => 'op',
          '#parents' => array('submit'),
        ),
        array(
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ),
        array(
          'test1' => 'foo',
          'submit' => 'baz',
          'op' => 'baz',
        ),
      ),
      // Test with a matching button #value and $form_state value.
      array(
        array(array('submit')),
        array(
          '#is_button' => TRUE,
          '#value' => 'baz',
          '#name' => 'op',
          '#parents' => array('submit'),
        ),
        array(
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ),
        array(
          'submit' => 'baz',
          'op' => 'baz',
        ),
      ),
      // Test with a mismatched button #value and $form_state value.
      array(
        array(array('submit')),
        array(
          '#is_button' => TRUE,
          '#value' => 'bar',
          '#name' => 'op',
          '#parents' => array('submit'),
        ),
        array(
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ),
        array(
          'submit' => 'baz',
        ),
      ),
    );
  }

  /**
   * @covers ::executeValidateHandlers
   */
  public function testExecuteValidateHandlers() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $mock = $this->getMock('stdClass', array('validate_handler', 'hash_validate'));
    $mock->expects($this->once())
      ->method('validate_handler')
      ->with($this->isType('array'), $this->isType('array'));
    $mock->expects($this->once())
      ->method('hash_validate')
      ->with($this->isType('array'), $this->isType('array'));

    $form = array();
    $form_state = $this->getFormStateDefaults();
    $form_validator->executeValidateHandlers($form, $form_state);

    $form['#validate'][] = array($mock, 'hash_validate');
    $form_validator->executeValidateHandlers($form, $form_state);

    // $form_state validate handlers will supersede $form handlers.
    $form_state['validate_handlers'][] = array($mock, 'validate_handler');
    $form_validator->executeValidateHandlers($form, $form_state);
  }

  /**
   * @covers ::doValidateForm
   *
   * @dataProvider providerTestRequiredErrorMessage
   */
  public function testRequiredErrorMessage($element, $expected_message) {
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array(new RequestStack(), $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('executeValidateHandlers', 'setErrorByName'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');
    $form_validator->expects($this->once())
      ->method('setErrorByName')
      ->with('test', $this->isType('array'), $expected_message);

    $form = array();
    $form['test'] = $element + array(
      '#type' => 'textfield',
      '#value' => '',
      '#needs_validation' => TRUE,
      '#required' => TRUE,
      '#parents' => array('test'),
    );
    $form_state = $this->getFormStateDefaults();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  public function providerTestRequiredErrorMessage() {
    return array(
      array(
        // Use the default message with a title.
        array('#title' => 'Test'),
        'Test field is required.',
      ),
      // Use a custom message.
      array(
        array('#required_error' => 'FAIL'),
        'FAIL',
      ),
      // No title or custom message.
      array(
        array(),
        '',
      ),
    );
  }

  /**
   * @covers ::doValidateForm
   */
  public function testElementValidate() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->disableOriginalConstructor()
      ->setMethods(array('executeValidateHandlers', 'setErrorByName'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');
    $mock = $this->getMock('stdClass', array('element_validate'));
    $mock->expects($this->once())
      ->method('element_validate')
      ->with($this->isType('array'), $this->isType('array'), NULL);

    $form = array();
    $form['test'] = array(
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => array('test'),
      '#element_validate' => array(array($mock, 'element_validate')),
    );
    $form_state = $this->getFormStateDefaults();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  /**
   * @covers ::performRequiredValidation
   *
   * @dataProvider providerTestPerformRequiredValidation
   */
  public function testPerformRequiredValidation($element, $expected_message, $call_watchdog) {
    $csrf_token = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs(array(new RequestStack(), $this->getStringTranslationStub(), $csrf_token))
      ->setMethods(array('setErrorByName', 'watchdog'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('setErrorByName')
      ->with('test', $this->isType('array'), $expected_message);

    if ($call_watchdog) {
      $form_validator->expects($this->once())
        ->method('watchdog')
        ->with('form');
    }

    $form = array();
    $form['test'] = $element + array(
      '#title' => 'Test',
      '#needs_validation' => TRUE,
      '#required' => FALSE,
      '#parents' => array('test'),
    );
    $form_state = $this->getFormStateDefaults();
    $form_state['values'] = array();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  public function providerTestPerformRequiredValidation() {
    return array(
      array(
        array(
          '#type' => 'select',
          '#options' => array(
            'foo' => 'Foo',
            'bar' => 'Bar',
          ),
          '#required' => TRUE,
          '#value' => 'baz',
          '#empty_value' => 'baz',
          '#multiple' => FALSE,
        ),
        'Test field is required.',
        FALSE,
      ),
      array(
        array(
          '#type' => 'select',
          '#options' => array(
            'foo' => 'Foo',
            'bar' => 'Bar',
          ),
          '#value' => 'baz',
          '#multiple' => FALSE,
        ),
        'An illegal choice has been detected. Please contact the site administrator.',
        TRUE,
      ),
      array(
        array(
          '#type' => 'checkboxes',
          '#options' => array(
            'foo' => 'Foo',
            'bar' => 'Bar',
          ),
          '#value' => array('baz'),
          '#multiple' => TRUE,
        ),
        'An illegal choice has been detected. Please contact the site administrator.',
        TRUE,
      ),
      array(
        array(
          '#type' => 'select',
          '#options' => array(
            'foo' => 'Foo',
            'bar' => 'Bar',
          ),
          '#value' => array('baz'),
          '#multiple' => TRUE,
        ),
        'An illegal choice has been detected. Please contact the site administrator.',
        TRUE,
      ),
      array(
        array(
          '#type' => 'textfield',
          '#maxlength' => 7,
          '#value' => $this->randomName(8),
        ),
        String::format('!name cannot be longer than %max characters but is currently %length characters long.', array('!name' => 'Test', '%max' => '7', '%length' => 8)),
        FALSE,
      ),
    );
  }

  /**
   * @return array()
   */
  protected function getFormStateDefaults() {
    $form_builder = $this->getMockBuilder('Drupal\Core\Form\FormBuilder')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    return $form_builder->getFormStateDefaults();
  }

}

}

namespace {
  if (!defined('WATCHDOG_ERROR')) {
    define('WATCHDOG_ERROR', 3);
  }
  if (!defined('WATCHDOG_NOTICE')) {
    define('WATCHDOG_NOTICE', 5);
  }
}
