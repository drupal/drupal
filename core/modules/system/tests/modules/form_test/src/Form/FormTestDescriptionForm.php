<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for testing form element description display options.
 *
 * @internal
 *
 * @see \Drupal\system\Tests\Form\ElementsLabelsTest::testFormDescriptions()
 */
class FormTestDescriptionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_description_display';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['form_textfield_test_description_before'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield test for description before element',
      '#description' => 'Textfield test for description before element',
      '#description_display' => 'before',
    ];

    $form['form_textfield_test_description_after'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield test for description after element',
      '#description' => 'Textfield test for description after element',
      '#description_display' => 'after',
    ];

    $form['form_textfield_test_description_invisible'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield test for visually-hidden description',
      '#description' => 'Textfield test for visually-hidden description',
      '#description_display' => 'invisible',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The test that uses this form does not submit the form so this is empty.
  }

}
