<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormErrorHandler
 * @group Form
 */
class FormErrorHandlerTest extends UnitTestCase {

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorHandler|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formErrorHandler;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->messenger = $this->createMock(MessengerInterface::class);

    $this->formErrorHandler = $this->getMockBuilder('Drupal\Core\Form\FormErrorHandler')
      ->setMethods(['messenger'])
      ->getMock();

    $this->formErrorHandler->expects($this->atLeastOnce())
      ->method('messenger')
      ->willReturn($this->messenger);
  }

  /**
   * @covers ::handleFormErrors
   * @covers ::displayErrorMessages
   */
  public function testDisplayErrorMessages() {
    $this->messenger->expects($this->at(0))
      ->method('addMessage')
      ->with('invalid', 'error');
    $this->messenger->expects($this->at(1))
      ->method('addMessage')
      ->with('invalid', 'error');
    $this->messenger->expects($this->at(2))
      ->method('addMessage')
      ->with('invalid', 'error');
    $this->messenger->expects($this->at(3))
      ->method('addMessage')
      ->with('no title given', 'error');
    $this->messenger->expects($this->at(4))
      ->method('addMessage')
      ->with('element is invisible', 'error');
    $this->messenger->expects($this->at(5))
      ->method('addMessage')
      ->with('this missing element is invalid', 'error');

    $form = [
      '#parents' => [],
      '#array_parents' => [],
    ];
    $form['test1'] = [
      '#type' => 'textfield',
      '#title' => 'Test 1',
      '#parents' => ['test1'],
      '#array_parents' => ['test1'],
      '#id' => 'edit-test1',
    ];
    $form['test2'] = [
      '#type' => 'textfield',
      '#title' => 'Test 2 & a half',
      '#parents' => ['test2'],
      '#array_parents' => ['test2'],
      '#id' => 'edit-test2',
    ];
    $form['fieldset'] = [
      '#parents' => ['fieldset'],
      '#array_parents' => ['fieldset'],
      'test3' => [
        '#type' => 'textfield',
        '#title' => 'Test 3',
        '#parents' => ['fieldset', 'test3'],
        '#array_parents' => ['fieldset', 'test3'],
        '#id' => 'edit-test3',
      ],
    ];
    $form['test5'] = [
      '#type' => 'textfield',
      '#parents' => ['test5'],
      '#array_parents' => ['test5'],
      '#id' => 'edit-test5',
    ];
    $form['test6'] = [
      '#type' => 'value',
      '#title' => 'Test 6',
      '#parents' => ['test6'],
      '#array_parents' => ['test6'],
      '#id' => 'edit-test6',
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test1', 'invalid');
    $form_state->setErrorByName('test2', 'invalid');
    $form_state->setErrorByName('fieldset][test3', 'invalid');
    $form_state->setErrorByName('test5', 'no title given');
    $form_state->setErrorByName('test6', 'element is invisible');
    $form_state->setErrorByName('missing_element', 'this missing element is invalid');
    $this->formErrorHandler->handleFormErrors($form, $form_state);
    $this->assertSame('invalid', $form['test1']['#errors']);
  }

  /**
   * @covers ::handleFormErrors
   * @covers ::setElementErrorsFromFormState
   */
  public function testSetElementErrorsFromFormState() {
    $form = [
      '#parents' => [],
      '#array_parents' => [],
    ];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#array_parents' => ['test'],
      '#id' => 'edit-test',
    ];
    $form['details'] = [
      '#type' => 'details',
      '#title' => 'Details grouping test',
      '#parents' => ['details'],
      '#array_parents' => ['details'],
      '#id' => 'edit-details',
    ];
    $form['grouping_test'] = [
      '#type' => 'textfield',
      '#title' => 'Grouping test',
      '#parents' => ['grouping_test'],
      '#array_parents' => ['grouping_test'],
      '#id' => 'edit-grouping-test',
      '#group' => 'details',
    ];
    $form['grouping_test2'] = [
      '#type' => 'textfield',
      '#title' => 'Grouping test 2',
      '#parents' => ['grouping_test2'],
      '#array_parents' => ['grouping_test2'],
      '#id' => 'edit-grouping-test2',
      '#group' => 'details',
    ];
    $form['details2'] = [
      '#type' => 'details',
      '#title' => 'Details grouping test 2',
      '#parents' => ['details2'],
      '#array_parents' => ['details2'],
      '#id' => 'edit-details2',
    ];
    $form['grouping_test3'] = [
      '#type' => 'textfield',
      '#title' => 'Grouping test 3',
      '#parents' => ['grouping_test3'],
      '#array_parents' => ['grouping_test3'],
      '#id' => 'edit-grouping-test3',
      '#group' => 'details2',
    ];
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#parents' => ['fieldset'],
      '#array_parents' => ['fieldset'],
      '#id' => 'edit-fieldset',
      'nested_test' => [
        '#type' => 'textfield',
        '#title' => 'Nested test',
        '#parents' => ['fieldset', 'nested_test'],
        '#array_parents' => ['fieldset', 'nested_test'],
        '#id' => 'edit-nested_test',
      ],
      'nested_test2' => [
        '#type' => 'textfield',
        '#title' => 'Nested test2',
        '#parents' => ['fieldset', 'nested_test2'],
        '#array_parents' => ['fieldset', 'nested_test2'],
        '#id' => 'edit-nested_test2',
      ],
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test', 'invalid');
    $form_state->setErrorByName('grouping_test', 'invalid');
    $form_state->setErrorByName('grouping_test2', 'invalid');
    $form_state->setErrorByName('fieldset][nested_test', 'invalid');
    $form_state->setErrorByName('fieldset][nested_test2', 'invalid2');
    $this->formErrorHandler->handleFormErrors($form, $form_state);
    $this->assertSame('invalid', $form['test']['#errors']);
    $this->assertSame([
      'grouping_test' => 'invalid',
      'grouping_test2' => 'invalid',
    ], $form['details']['#children_errors']);
    $this->assertSame([
      'fieldset][nested_test' => 'invalid',
      'fieldset][nested_test2' => 'invalid2',
    ], $form['fieldset']['#children_errors']);
    $this->assertEmpty($form['details2']['#children_errors'], 'Children errors are empty for grouping element.');
    $this->assertCount(5, $form['#children_errors']);
  }

}
