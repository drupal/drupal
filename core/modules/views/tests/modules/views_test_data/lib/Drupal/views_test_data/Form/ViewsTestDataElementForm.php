<?php
/**
 * @file
 * Contains \Drupal\views_test_data\Form\ViewsTestDataElementForm.
 */

namespace Drupal\views_test_data\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Simple form page callback to test the view element.
 */
class ViewsTestDataElementForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_test_data_element_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['view'] = array(
      '#type' => 'view',
      '#name' => 'test_view',
      '#display_id' => 'default',
      '#arguments' => array(25),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
