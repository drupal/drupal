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
