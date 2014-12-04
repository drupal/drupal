<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Form\AjaxTestForm.
 */

namespace Drupal\ajax_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dummy form for testing DialogRenderer with _form routes.
 */
class AjaxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#action'] = \Drupal::url('ajax_test.dialog');

    $form['description'] = array(
      '#markup' => '<p>' . $this->t("Ajax Form contents description.") . '</p>',
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Do it'),
    );
    $form['actions']['preview'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        // This means the ::preview() method on this class would be invoked in
        // case of a click event. However, since Drupal core's test runner only
        // is able to execute PHP, not JS, there is no point in actually
        // implementing this method, because we can never let it be called from
        // JS; we'd have to manually call it from PHP, at which point we would
        // not actually be testing it.
        // Therefore we consciously choose to not implement this method, because
        // we cannot meaningfully test it anyway.
        'callback' => '::preview',
        'event' => 'click',
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
