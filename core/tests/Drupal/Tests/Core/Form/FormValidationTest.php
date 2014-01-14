<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormValidationTest.
 */

namespace Drupal\Tests\Core\Form;

/**
 * Tests various form element validation mechanisms.
 *
 * @group Drupal
 * @group Form
 */
class FormValidationTest extends FormTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Form element validation',
      'description' => 'Tests various form element validation mechanisms.',
      'group' => 'Form API',
    );
  }

  public function testNoDuplicateErrorsForIdenticalForm() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['test']['#required'] = TRUE;

    // Mock a form object that will be built three times.
    $form_arg = $this->getMockForm($form_id, $expected_form, 3);

    // The first form will have errors.
    $form_state = array();
    $this->formBuilder->getFormId($form_arg, $form_state);
    $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertNotEmpty($errors['test']);

    // The second form will not have errors.
    $form_state = array();
    $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertEmpty($errors);

    // Reset the form builder.
    $this->setupFormBuilder();

    // On a new request, the first form will have errors again.
    $form_state = array();
    $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $errors = $this->formBuilder->getErrors($form_state);
    $this->assertNotEmpty($errors['test']);
  }

  public function testUniqueHtmlId() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['test']['#required'] = TRUE;

    // Mock a form object that will be built three times.
    $form_arg = $this->getMockForm($form_id, $expected_form, 2);

    $form_state = array();
    $this->formBuilder->getFormId($form_arg, $form_state);
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame($form_id, $form['#id']);

    $form_state = array();
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame("$form_id--2", $form['#id']);
  }

}
