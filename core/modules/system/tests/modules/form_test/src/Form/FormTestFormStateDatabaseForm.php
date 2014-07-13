<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestFormStateDatabaseForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;

/**
 * Builds a form which gets the database connection stored in the form state.
 */
class FormTestFormStateDatabaseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_state_database';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text field'),
    );

    $form['test_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    $db = Database::getConnection('default');
    $form_state['storage']['database'] = $db;
    $form_state['storage']['database_class'] = get_class($db);

    if (isset($form_state['storage']['database_connection_found'])) {
      $form['database']['#markup'] = 'Database connection found';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['cache'] = TRUE;
    $form_state['rebuild'] = TRUE;

    if ($form_state['storage']['database'] instanceof $form_state['storage']['database_class']) {
      $form_state['storage']['database_connection_found'] = TRUE;
    }
  }

}
