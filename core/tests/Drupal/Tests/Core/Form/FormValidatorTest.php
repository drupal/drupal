<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\TestTools\Random;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormValidator
 * @group Form
 */
class FormValidatorTest extends UnitTestCase {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $csrfToken;

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formErrorHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->logger = $this->createMock('Psr\Log\LoggerInterface');
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->formErrorHandler = $this->createMock('Drupal\Core\Form\FormErrorHandlerInterface');
  }

  /**
   * Tests the 'validation_complete' $form_state flag.
   *
   * @covers ::validateForm
   * @covers ::finalizeValidation
   */
  public function testValidationComplete(): void {
    $form_validator = new FormValidator(new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler);

    $form = [];
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
  public function testPreventDuplicateValidation(): void {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['doValidateForm'])
      ->getMock();
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form = [];
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
  public function testMustValidate(): void {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['doValidateForm'])
      ->getMock();
    $form_validator->expects($this->once())
      ->method('doValidateForm');
    $this->formErrorHandler->expects($this->once())
      ->method('handleFormErrors');

    $form = [];
    $form_state = (new FormState())
      ->setValidationComplete()
      ->setValidationEnforced();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateInvalidFormToken(): void {
    $request_stack = new RequestStack();
    $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/test/example?foo=bar']);
    $request_stack->push($request);
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->willReturn(FALSE);

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([$request_stack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['doValidateForm'])
      ->getMock();
    $form_validator->expects($this->never())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['setErrorByName'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('setErrorByName')
      ->with('form_token', 'The form has become outdated. Press the back button, copy any unsaved work in the form, and then reload the page.');
    $form_state->setValue('form_token', 'some_random_token');
    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertTrue($form_state->isValidationComplete());
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateValidFormToken(): void {
    $request_stack = new RequestStack();
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->willReturn(TRUE);

    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([$request_stack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['doValidateForm'])
      ->getMock();
    $form_validator->expects($this->once())
      ->method('doValidateForm');

    $form['#token'] = 'test_form_id';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['setErrorByName'])
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
  public function testHandleErrorsWithLimitedValidation($sections, $triggering_element, $values, $expected): void {
    $form_validator = new FormValidator(new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler);

    $triggering_element['#limit_validation_errors'] = $sections;
    $form = [];
    $form_state = (new FormState())
      ->setValues($values)
      ->setTriggeringElement($triggering_element);

    $form_validator->validateForm('test_form_id', $form, $form_state);
    $this->assertSame($expected, $form_state->getValues());
  }

  public static function providerTestHandleErrorsWithLimitedValidation() {
    return [
      // Test with a non-existent section.
      [
        [['test1'], ['test3']],
        [],
        [
          'test1' => 'foo',
          'test2' => 'bar',
        ],
        [
          'test1' => 'foo',
        ],
      ],
      // Test with buttons in a non-validated section.
      [
        [['test1']],
        [
          '#is_button' => TRUE,
          '#value' => 'baz',
          '#name' => 'op',
          '#parents' => ['submit'],
        ],
        [
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ],
        [
          'test1' => 'foo',
          'submit' => 'baz',
          'op' => 'baz',
        ],
      ],
      // Test with a matching button #value and $form_state value.
      [
        [['submit']],
        [
          '#is_button' => TRUE,
          '#value' => 'baz',
          '#name' => 'op',
          '#parents' => ['submit'],
        ],
        [
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ],
        [
          'submit' => 'baz',
          'op' => 'baz',
        ],
      ],
      // Test with a mismatched button #value and $form_state value.
      [
        [['submit']],
        [
          '#is_button' => TRUE,
          '#value' => 'bar',
          '#name' => 'op',
          '#parents' => ['submit'],
        ],
        [
          'test1' => 'foo',
          'test2' => 'bar',
          'op' => 'baz',
          'submit' => 'baz',
        ],
        [
          'submit' => 'baz',
        ],
      ],
    ];
  }

  /**
   * @covers ::executeValidateHandlers
   */
  public function testExecuteValidateHandlers(): void {
    $form_validator = new FormValidator(new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler);

    $mock = $this->getMockBuilder(FormValidatorTestMockInterface::class)
      ->onlyMethods(['validate_handler', 'hash_validate', 'element_validate'])
      ->getMock();
    $mock->expects($this->once())
      ->method('validate_handler')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));
    $mock->expects($this->once())
      ->method('hash_validate')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));

    $form = [];
    $form_state = new FormState();
    $form_validator->executeValidateHandlers($form, $form_state);

    $form['#validate'][] = [$mock, 'hash_validate'];
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
  public function testRequiredErrorMessage($element, $expected_message): void {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['executeValidateHandlers'])
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');

    $form = [];
    $form['test'] = $element + [
      '#type' => 'textfield',
      '#value' => '',
      '#needs_validation' => TRUE,
      '#required' => TRUE,
      '#parents' => ['test'],
    ];
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['setError'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('setError')
      ->with($this->isType('array'), $expected_message);
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  public static function providerTestRequiredErrorMessage() {
    return [
      [
        // Use the default message with a title.
        ['#title' => 'Test'],
        'Test field is required.',
      ],
      // Use a custom message.
      [
        ['#required_error' => 'FAIL'],
        'FAIL',
      ],
      // No title or custom message.
      [
        [],
        '',
      ],
    ];
  }

  /**
   * @covers ::doValidateForm
   */
  public function testElementValidate(): void {
    $form_validator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler])
      ->onlyMethods(['executeValidateHandlers'])
      ->getMock();
    $form_validator->expects($this->once())
      ->method('executeValidateHandlers');
    $mock = $this->getMockBuilder(FormValidatorTestMockInterface::class)
      ->onlyMethods(['validate_handler', 'hash_validate', 'element_validate'])
      ->getMock();
    $mock->expects($this->once())
      ->method('element_validate')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'), NULL);

    $form = [];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#element_validate' => [[$mock, 'element_validate']],
    ];
    $form_state = new FormState();
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  /**
   * @covers ::performRequiredValidation
   *
   * @dataProvider providerTestPerformRequiredValidation
   */
  public function testPerformRequiredValidation($element, $expected_message, $call_watchdog): void {
    $form_validator = new FormValidator(new RequestStack(), $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $this->formErrorHandler);

    if ($call_watchdog) {
      $this->logger->expects($this->once())
        ->method('error')
        ->with($this->isType('string'), $this->isType('array'));
    }

    $form = [];
    $form['test'] = $element + [
      '#title' => 'Test',
      '#needs_validation' => TRUE,
      '#required' => FALSE,
      '#parents' => ['test'],
    ];
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['setError'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('setError')
      ->with($this->isType('array'), $expected_message);
    $form_validator->validateForm('test_form_id', $form, $form_state);
  }

  public static function providerTestPerformRequiredValidation() {
    return [
      [
        [
          '#type' => 'select',
          '#options' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
          ],
          '#required' => TRUE,
          '#value' => 'baz',
          '#empty_value' => 'baz',
          '#multiple' => FALSE,
        ],
        'Test field is required.',
        FALSE,
      ],
      [
        [
          '#type' => 'select',
          '#options' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
          ],
          '#value' => 'baz',
          '#multiple' => FALSE,
        ],
        'The submitted value <em class="placeholder">baz</em> in the <em class="placeholder">Test</em> element is not allowed.',
        TRUE,
      ],
      [
        [
          '#type' => 'checkboxes',
          '#options' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
          ],
          '#value' => ['baz'],
          '#multiple' => TRUE,
        ],
        'The submitted value <em class="placeholder">0</em> in the <em class="placeholder">Test</em> element is not allowed.',
        TRUE,
      ],
      [
        [
          '#type' => 'select',
          '#options' => [
            'foo' => 'Foo',
            'bar' => 'Bar',
          ],
          '#value' => ['baz'],
          '#multiple' => TRUE,
        ],
        'The submitted value <em class="placeholder">baz</em> in the <em class="placeholder">Test</em> element is not allowed.',
        TRUE,
      ],
      [
        [
          '#type' => 'textfield',
          '#maxlength' => 7,
          '#value' => Random::machineName(8),
        ],
        'Test cannot be longer than <em class="placeholder">7</em> characters but is currently <em class="placeholder">8</em> characters long.',
        FALSE,
      ],
    ];
  }

}

/**
 * Interface used in the mocking process of this test.
 */
interface FormValidatorTestMockInterface {

  /**
   * Function used in the mocking process of this test.
   */
  public function validate_handler();

  /**
   * Function used in the mocking process of this test.
   */
  public function hash_validate();

  /**
   * Function used in the mocking process of this test.
   */
  public function element_validate();

}
