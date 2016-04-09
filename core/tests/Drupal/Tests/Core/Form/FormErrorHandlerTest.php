<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormErrorHandler
 * @group Form
 */
class FormErrorHandlerTest extends UnitTestCase {

  /**
   * @covers ::handleFormErrors
   * @covers ::displayErrorMessages
   */
  public function testDisplayErrorMessages() {
    $form_error_handler = $this->getMockBuilder('Drupal\Core\Form\FormErrorHandler')
      ->setMethods(['drupalSetMessage'])
      ->getMock();

    $form_error_handler->expects($this->at(0))
      ->method('drupalSetMessage')
      ->with('invalid', 'error');
    $form_error_handler->expects($this->at(1))
      ->method('drupalSetMessage')
      ->with('invalid', 'error');
    $form_error_handler->expects($this->at(2))
      ->method('drupalSetMessage')
      ->with('invalid', 'error');
    $form_error_handler->expects($this->at(3))
      ->method('drupalSetMessage')
      ->with('no title given', 'error');
    $form_error_handler->expects($this->at(4))
      ->method('drupalSetMessage')
      ->with('element is invisible', 'error');
    $form_error_handler->expects($this->at(5))
      ->method('drupalSetMessage')
      ->with('this missing element is invalid', 'error');

    $form = [
      '#parents' => [],
    ];
    $form['test1'] = [
      '#type' => 'textfield',
      '#title' => 'Test 1',
      '#parents' => ['test1'],
      '#id' => 'edit-test1',
    ];
    $form['test2'] = [
      '#type' => 'textfield',
      '#title' => 'Test 2 & a half',
      '#parents' => ['test2'],
      '#id' => 'edit-test2',
    ];
    $form['fieldset'] = [
      '#parents' => ['fieldset'],
      'test3' => [
        '#type' => 'textfield',
        '#title' => 'Test 3',
        '#parents' => ['fieldset', 'test3'],
        '#id' => 'edit-test3',
      ],
    ];
    $form['test5'] = [
      '#type' => 'textfield',
      '#parents' => ['test5'],
      '#id' => 'edit-test5',
    ];
    $form['test6'] = [
      '#type' => 'value',
      '#title' => 'Test 6',
      '#parents' => ['test6'],
      '#id' => 'edit-test6',
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test1', 'invalid');
    $form_state->setErrorByName('test2', 'invalid');
    $form_state->setErrorByName('fieldset][test3', 'invalid');
    $form_state->setErrorByName('test5', 'no title given');
    $form_state->setErrorByName('test6', 'element is invisible');
    $form_state->setErrorByName('missing_element', 'this missing element is invalid');
    $form_error_handler->handleFormErrors($form, $form_state);
    $this->assertSame('invalid', $form['test1']['#errors']);
  }

  /**
   * @covers ::handleFormErrors
   * @covers ::setElementErrorsFromFormState
   */
  public function testSetElementErrorsFromFormState() {
    $form_error_handler = $this->getMockBuilder('Drupal\Core\Form\FormErrorHandler')
      ->setMethods(['drupalSetMessage'])
      ->getMock();

    $form = [
      '#parents' => [],
    ];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#id' => 'edit-test',
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test', 'invalid');
    $form_error_handler->handleFormErrors($form, $form_state);
    $this->assertSame('invalid', $form['test']['#errors']);
  }

}
