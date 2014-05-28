<?php

/**
 * @file
 * Contains \Drupal\user_form_test\Form\TestCurrentPassword.
 */

namespace Drupal\user_form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\user\UserInterface;

/**
 * Provides a current password validation form.
 */
class TestCurrentPassword extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_form_test_current_password';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\user\Entity\UserInterface $user
   *   The user account.
   */
  public function buildForm(array $form, array &$form_state, UserInterface $user = NULL) {
    $form_state['user'] = $user ;
    $form['user_form_test_field'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Test field'),
      '#description' => $this->t('A field that would require a correct password to change.'),
      '#required' => TRUE,
    );

    $form['current_pass'] = array(
      '#type' => 'password',
      '#title' => $this->t('Current password'),
      '#size' => 25,
      '#description' => $this->t('Enter your current password'),
    );

    $form['current_pass_required_values'] = array(
      '#type' => 'value',
      '#value' => array('user_form_test_field' => $this->t('Test field')),
    );

    $form['#validate'][] = 'user_validate_current_pass';
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Test'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message($this->t('The password has been validated and the form submitted successfully.'));
  }

}
