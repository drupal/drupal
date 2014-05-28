<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\TriggeringElementProgrammedUnitTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests detection of triggering_element for programmed form submissions.
 */
class TriggeringElementProgrammedUnitTest extends DrupalUnitTestBase implements FormInterface {

  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'Form triggering element programmed determination',
      'description' => 'Tests detection of triggering_element for programmed form submissions.',
      'group' => 'Form API',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triggering_element_programmed_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['one'] = array(
      '#type' => 'textfield',
      '#title' => 'One',
      '#required' => TRUE,
    );
    $form['two'] = array(
      '#type' => 'textfield',
      '#title' => 'Two',
      '#required' => TRUE,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
      '#limit_validation_errors' => array(
        array($form_state['input']['section']),
      ),
      // Required for #limit_validation_errors.
      '#submit' => array(array($this, 'submitForm')),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Verify that the only submit button was recognized as triggering_element.
    $this->assertEqual($form['actions']['submit']['#array_parents'], $form_state['triggering_element']['#array_parents']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

  /**
   * Tests that #limit_validation_errors of the only submit button takes effect.
   */
  function testLimitValidationErrors() {
    // Programmatically submit the form.
    $form_state['values'] = array();
    $form_state['values']['section'] = 'one';
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Verify that only the specified section was validated.
    $errors = $form_builder->getErrors($form_state);
    $this->assertTrue(isset($errors['one']), "Section 'one' was validated.");
    $this->assertFalse(isset($errors['two']), "Section 'two' was not validated.");

    // Verify that there are only values for the specified section.
    $this->assertTrue(isset($form_state['values']['one']), "Values for section 'one' found.");
    $this->assertFalse(isset($form_state['values']['two']), "Values for section 'two' not found.");
  }

}
