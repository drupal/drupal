<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestFormStateDatabaseForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text field'),
    );

    $form['test_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    $db = Database::getConnection('default');
    $form_state->set('database', $db);
    $form_state->set('database_class', get_class($db));

    if ($form_state->has('database_connection_found')) {
      $form['database']['#markup'] = 'Database connection found';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setCached();
    $form_state->setRebuild();

    $database_class = $form_state->get('database_class');
    if ($form_state->get('database') instanceof $database_class) {
      $form_state->set('database_connection_found', TRUE);
    }
  }

}
