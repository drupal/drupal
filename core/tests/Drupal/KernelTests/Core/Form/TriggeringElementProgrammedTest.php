<?php

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests detection of triggering_element for programmed form submissions.
 *
 * @group Form
 */
class TriggeringElementProgrammedTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triggering_element_programmed_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['one'] = [
      '#type' => 'textfield',
      '#title' => 'One',
      '#required' => TRUE,
    ];
    $form['two'] = [
      '#type' => 'textfield',
      '#title' => 'Two',
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $user_input = $form_state->getUserInput();
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#limit_validation_errors' => [
        [$user_input['section']],
      ],
      // Required for #limit_validation_errors.
      '#submit' => [[$this, 'submitForm']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the only submit button was recognized as triggering_element.
    $this->assertEqual($form['actions']['submit']['#array_parents'], $form_state->getTriggeringElement()['#array_parents']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Tests that #limit_validation_errors of the only submit button takes effect.
   */
  public function testLimitValidationErrors() {
    // Programmatically submit the form.
    $form_state = new FormState();
    $form_state->setValue('section', 'one');
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Verify that only the specified section was validated.
    $errors = $form_state->getErrors();
    $this->assertTrue(isset($errors['one']), "Section 'one' was validated.");
    $this->assertFalse(isset($errors['two']), "Section 'two' was not validated.");

    // Verify that there are only values for the specified section.
    $this->assertTrue($form_state->hasValue('one'), "Values for section 'one' found.");
    $this->assertFalse($form_state->hasValue('two'), "Values for section 'two' not found.");
  }

}
