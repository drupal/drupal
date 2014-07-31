<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestGroupVerticalTabsForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #group property on #type 'vertical_tabs'.
 */
class FormTestGroupVerticalTabsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_group_vertical_tabs';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['vertical_tabs'] = array(
      '#type' => 'vertical_tabs',
    );
    $form['meta'] = array(
      '#type' => 'details',
      '#title' => 'First group element',
      '#group' => 'vertical_tabs',
    );
    $form['meta']['element'] = array(
      '#type' => 'textfield',
      '#title' => 'First nested element in details element',
    );
    $form['meta_2'] = array(
      '#type' => 'details',
      '#title' => 'Second group element',
      '#group' => 'vertical_tabs',
    );
    $form['meta_2']['element_2'] = array(
      '#type' => 'textfield',
      '#title' => 'Second nested element in details element',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
