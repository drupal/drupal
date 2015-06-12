<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\TriggeringElementProgrammedUnitTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests detection of triggering_element for programmed form submissions.
 *
 * @group Form
 */
class TriggeringElementProgrammedUnitTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
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
  public function buildForm(array $form, FormStateInterface $form_state) {
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
    $user_input = $form_state->getUserInput();
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
      '#limit_validation_errors' => array(
        array($user_input['section']),
      ),
      // Required for #limit_validation_errors.
      '#submit' => array(array($this, 'submitForm')),
    );
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
  function testLimitValidationErrors() {
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
