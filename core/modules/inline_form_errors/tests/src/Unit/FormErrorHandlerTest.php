<?php

namespace Drupal\Tests\inline_form_errors\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\inline_form_errors\FormErrorHandler;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\inline_form_errors\FormErrorHandler
 * @group InlineFormErrors
 */
class FormErrorHandlerTest extends UnitTestCase {

  /**
   * @covers ::handleFormErrors
   * @covers ::displayErrorMessages
   */
  public function testDisplayErrorMessagesInline() {
    $link_generator = $this->getMock(LinkGeneratorInterface::class);
    $link_generator->expects($this->any())
      ->method('generate')
      ->willReturnArgument(0);
    $renderer = $this->getMock(RendererInterface::class);
    $form_error_handler = $this->getMockBuilder(FormErrorHandler::class)
      ->setConstructorArgs([$this->getStringTranslationStub(), $link_generator, $renderer])
      ->setMethods(['drupalSetMessage'])
      ->getMock();

    $form_error_handler->expects($this->at(0))
      ->method('drupalSetMessage')
      ->with('no title given', 'error');
    $form_error_handler->expects($this->at(1))
      ->method('drupalSetMessage')
      ->with('element is invisible', 'error');
    $form_error_handler->expects($this->at(2))
      ->method('drupalSetMessage')
      ->with('this missing element is invalid', 'error');
    $form_error_handler->expects($this->at(3))
      ->method('drupalSetMessage')
      ->with('3 errors have been found: <ul-comma-list-mock><li-mock>Test 1</li-mock><li-mock>Test 2 &amp; a half</li-mock><li-mock>Test 3</li-mock></ul-comma-list-mock>', 'error');

    $renderer->expects($this->any())
      ->method('renderPlain')
      ->will($this->returnCallback(function ($render_array) {
        return $render_array[0]['#markup'] . '<ul-comma-list-mock><li-mock>' . implode(array_map('htmlspecialchars', $render_array[1]['#items']), '</li-mock><li-mock>') . '</li-mock></ul-comma-list-mock>';
      }));

    $form = [
      '#parents' => [],
      '#form_id' => 'test_form',
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
    $form['test4'] = [
      '#type' => 'textfield',
      '#title' => 'Test 4',
      '#parents' => ['test4'],
      '#array_parents' => ['test4'],
      '#id' => 'edit-test4',
      '#error_no_message' => TRUE,
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
    $form_state->setErrorByName('test4', 'no error message');
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
    $form_error_handler = $this->getMockBuilder(FormErrorHandler::class)
      ->setConstructorArgs([$this->getStringTranslationStub(), $this->getMock(LinkGeneratorInterface::class), $this->getMock(RendererInterface::class)])
      ->setMethods(['drupalSetMessage'])
      ->getMock();

    $form = [
      '#parents' => [],
      '#form_id' => 'test_form',
      '#array_parents' => [],
    ];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#array_parents' => ['test'],
      '#id' => 'edit-test',
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test', 'invalid');
    $form_error_handler->handleFormErrors($form, $form_state);
    $this->assertSame('invalid', $form['test']['#errors']);
  }

  /**
   * Test that Quick Edit forms show non-inline errors.
   *
   * @covers ::handleFormErrors
   * @covers ::displayErrorMessages
   */
  public function testDisplayErrorMessagesNotInlineQuickEdit() {
    $form_error_handler = $this->getMockBuilder(FormErrorHandler::class)
      ->setConstructorArgs([$this->getStringTranslationStub(), $this->getMock(LinkGeneratorInterface::class), $this->getMock(RendererInterface::class)])
      ->setMethods(['drupalSetMessage'])
      ->getMock();

    $form_error_handler->expects($this->at(0))
      ->method('drupalSetMessage')
      ->with('invalid', 'error');

    $form = [
      '#parents' => [],
      '#form_id' => 'quickedit_field_form',
      '#array_parents' => [],
    ];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#id' => 'edit-test',
      '#array_parents' => ['test']
    ];
    $form_state = new FormState();
    $form_state->setErrorByName('test', 'invalid');
    $form_error_handler->handleFormErrors($form, $form_state);
  }

}
