<?php

declare(strict_types=1);

namespace Drupal\Tests\inline_form_errors\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests messages on form elements.
 *
 * @group InlineFormErrors
 */
class FormElementInlineErrorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['inline_form_errors'];

  /**
   * Tests that no inline form errors are shown when disabled for a form.
   */
  public function testDisplayErrorMessagesNotInline(): void {
    $form_id = 'test';

    $form = [
      '#parents' => [],
      '#disable_inline_form_errors' => TRUE,
      '#array_parents' => [],
    ];
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => 'Test',
      '#parents' => ['test'],
      '#id' => 'edit-test',
      '#array_parents' => ['test'],
    ];
    $form_state = new FormState();

    \Drupal::formBuilder()->prepareForm($form_id, $form, $form_state);
    \Drupal::formBuilder()->processForm($form_id, $form, $form_state);

    // Just test if the #error_no_message property is TRUE. FormErrorHandlerTest
    // tests if the property actually hides the error message.
    $this->assertTrue($form['test']['#error_no_message']);
  }

}
