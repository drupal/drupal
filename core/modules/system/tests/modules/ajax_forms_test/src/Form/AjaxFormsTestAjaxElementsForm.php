<?php

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\ajax_forms_test\Callbacks;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that has each FAPI elements triggering a simple
 * Ajax callback.
 */
class AjaxFormsTestAjaxElementsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_forms_test_ajax_elements_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $callback_object = new Callbacks();

    $form['date'] = [
      '#type' => 'date',
      '#ajax' => [
        'callback' => [$callback_object, 'dateCallback'],
      ],
      '#suffix' => '<div id="ajax_date_value">No date yet selected</div>',
    ];

    $form['datetime'] = [
      '#type' => 'datetime',
      '#ajax' => [
        'callback' => [$callback_object, 'datetimeCallback'],
        'wrapper' => 'ajax_datetime_value',
      ],
    ];

    $form['datetime_result'] = [
      '#type' => 'markup',
      '#markup' => '<div id="ajax_datetime_value">No datetime selected.</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
