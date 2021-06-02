<?php

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Dependent element triggers a simple AJAX callback.
 *
 * @internal
 */
class AjaxFormsTestStatesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_states';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Ajax callback is used instead.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['num'] = [
      '#type' => 'radios',
      '#title' => 'Number',
      '#options' => ['First' => 'First', 'Second' => 'Second'],
      '#default_value' => 'First',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['color'] = [
      '#type' => 'radios',
      '#title' => 'Color',
      '#options' => ['Red' => 'Red', 'Green' => 'Green'],
      '#default_value' => 'Red',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form += $this->textFields();

    $form['data'] = [
      '#type' => 'item',
      '#title' => 'AJAX section:',
      '#prefix' => '<div id="states-bug-data-wrapper">',
      '#suffix' => '</div>',
    ];
    // A quick way of not adding the extra form items on first load:
    if ($this->getRequest()->getMethod() === 'POST') {
      $form['data'] += $this->textFields('extra_');
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#ajax' => [
        'callback' => '::statesBugData',
        'wrapper' => 'states-bug-data-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * Ajax submit callback.
   */
  public function statesBugData(array $form, FormStateInterface $form_state) {
    return $form['data'];
  }

  /**
   * Returns 4 text fields with differing #states.
   *
   * @param string $prefix
   */
  private function textFields($prefix = '') {
    $form[$prefix . 'textfield1'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 1 (depends on First-Red)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'First'],
          ':input[name="color"]' => ['value' => 'Red'],
        ],
      ],
    ];

    $form[$prefix . 'textfield2'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 2 (depends on First-Green)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'First'],
          ':input[name="color"]' => ['value' => 'Green'],
        ],
      ],
    ];

    $form[$prefix . 'textfield3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 3 (depends on Second-Red)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'Second'],
          ':input[name="color"]' => ['value' => 'Red'],
        ],
      ],
    ];

    $form[$prefix . 'textfield4'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 4 (depends on Second-Green)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'Second'],
          ':input[name="color"]' => ['value' => 'Green'],
        ],
      ],
    ];

    return $form;
  }

}
