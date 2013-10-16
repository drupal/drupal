<?php

/**
 * @file
 * Contains \Drupal\ajax_test\AjaxTestForm.
 */

namespace Drupal\ajax_test;

use Drupal\Core\Form\FormInterface;

/**
 * Dummy form for testing DialogController with _form routes.
 */
class AjaxTestForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    drupal_set_title(t('Ajax Form contents'));

    $form['#action'] = url('ajax-test/dialog');
    $form['#cache'] = TRUE;

    $form['description'] = array(
      '#markup' => '<p>' . t("Ajax Form contents description.") . '</p>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Do it'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {}

}
