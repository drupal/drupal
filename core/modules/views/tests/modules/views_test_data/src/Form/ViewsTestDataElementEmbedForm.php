<?php
/**
 * @file
 * Contains \Drupal\views_test_data\Form\ViewsTestDataElementEmbedForm.
 */

namespace Drupal\views_test_data\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple form page callback to test the view element.
 */
class ViewsTestDataElementEmbedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_test_data_element_embed_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['view'] = array(
      '#type' => 'view',
      '#name' => 'test_view_embed',
      '#display_id' => 'embed_1',
      '#arguments' => array(25),
      '#embed' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
