<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormValidator
 * @group Form
 */
class FormValidatorTest extends UnitTestCase {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formErrorHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->formErrorHandler = $this->getMock('Drupal\Core\Form\FormErrorHandlerInterface');
  }

  /**
   * Tests the 'validation_complete' $form_state flag.
   *
   * @covers ::validateForm
   * @covers ::finalizeValidation
   */
  public function testValidationComplete() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(NULL)
      ->getMock();

    $form = array();
    $form_state = new FormState();
    $this->assertFalse($form_state->isValidationComplete());
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state->isValidationComplete());
  }

  /**
   * Tests the 'must_validate' $form_state flag.
   *
   * @covers ::validateForm
   */
  public function testPreventDuplicateValidation() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form = array();
    $form_state = (new FormState())
      ->setValidationComplete();
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
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('doValidateForm');
    $this->formErrorHandler->expects($this->once())
      ->method('handleFormErrors');

    $form = array();
    $form_state = (new FormState())
      ->setValidationComplete()
      ->setValidationEnforced();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateInvalidFormToken() {
    $request_stack = new RequestStack();
    $request = new Request(array(), array(), array(), array(), array(), array('REQUEST_URI' => '/test/example?foo=bar'));
    $request_stack->push($request);
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(FALSE));

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([$request_stack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('setErrorByName'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('form_token', 'The form has become outdated. Copy any unsaved work in the form below and then <a href="/test/example?foo=bar">reload this page</a>.');
    $form_state->setValue('form_token', 'some_random_token');
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state->isValidationComplete());
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateValidFormToken() {
    $request_stack = new RequestStack();
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(TRUE));

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([$request_stack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('doValidateForm'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('setErrorByName'))
      ->getMock();
    $form_state->expects($this->never())
      ->method('setErrorByName');
    $form_state->setValue('form_token', 'some_random_token');
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state->isValidationComplete());
  }

  /**
   * @covers ::handleErrorsWithLimitedValidation
   *
   * @dataProvider providerTestHandleErrorsWithLimitedValidation
   */
  public function testHandleErrorsWithLimitedValidation($sections, $triggering_element, $values, $expected) {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(NULL)
      ->getMock();

    $triggering_element['#limit_validation_errors'] = $sections;
    $form = array();
    $form_state = (new FormState())
      ->setValues($values)
      ->setTriggeringElement($triggering_element);

    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertSame($expected, $form_state->getValues());
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
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(NULL)
      ->getMock();
    $mock = $this->getMock('stdClass', array('validate_handler', 'hash_validate'));
    $mock->expects($this->once())
      ->method('validate_handler')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));
    $mock->expects($this->once())
      ->method('hash_validate')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));

    $form = array();
    $form_state = new FormState();
    $form_validator->executeValidateHandlers($form, $form_state);

    $form['#validate'][] = array($mock, 'hash_validate');
    $form_validator->executeValidateHandlers($form, $form_state);

    // $form_state validate handlers will supersede $form handlers.
    $validate_handlers[] = [$mock, 'validate_handler'];
    $form_state->setValidateHandlers($validate_handlers);
    $form_validator->executeValidateHandlers($form, $form_state);
  }

  /**
   * @covers ::doValidateForm
   *
   * @dataProvider providerTestRequiredErrorMessage
   */
  public function testRequiredErrorMessage($element, $expected_message) {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('executeValidateHandlers'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');

    $form = array();
    $form['test'] = $element + array(
      '#type' => 'textfield',
      '#value' => '',
      '#needs_validation' => TRUE,
      '#required' => TRUE,
      '#parents' => array('test'),
    );
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('setError'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('setError')
      ->with($this->isType('array'), $expected_message);
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
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('executeValidateHandlers'))
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');
    $mock = $this->getMock('stdClass', array('element_validate'));
    $mock->expects($this->once())
      ->method('element_validate')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'), NULL);

    $form = array();
    $form['test'] = array(
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => array('test'),
      '#element_validate' => array(array($mock, 'element_validate')),
    );
    $form_state = new FormState();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  /**
   * @covers ::performRequiredValidation
   *
   * @dataProvider providerTestPerformRequiredValidation
   */
  public function testPerformRequiredValidation($element, $expected_message, $call_watchdog) {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->setMethods(array('setError'))
      ->getMock();

    if ($call_watchdog) {
      $this->logger->expects($this->once())
        ->method('error')
        ->with($this->isType('string'), $this->isType('array'));
    }

    $form = array();
    $form['test'] = $element + array(
      '#title' => 'Test',
      '#needs_validation' => TRUE,
      '#required' => FALSE,
      '#parents' => array('test'),
    );
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('setError'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('setError')
      ->with($this->isType('array'), $expected_message);
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
          '#value' => $this->randomMachineName(8),
        ),
        'Test cannot be longer than <em class="placeholder">7</em> characters but is currently <em class="placeholder">8</em> characters long.',
        FALSE,
      ),
    );
  }

}
