<?php

declare(strict_types=1);

namespace Drupal\ajax_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing AJAX FocusFirstCommand.
 *
 * @internal
 */
class AjaxTestFocusFirstForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_focus_first_command_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['first_input'] = [
      '#type' => 'textfield',
    ];
    $form['second_input'] = [
      '#type' => 'textfield',
    ];
    $form['a_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'a-container',
      ],
    ];
    $form['a_container']['first_container_input'] = [
      '#type' => 'textfield',
    ];
    $form['a_container']['second_container_input'] = [
      '#type' => 'textfield',
    ];
    $form['focusable_container_without_tabbable_children'] = [
      '#type' => 'container',
      '#attributes' => [
        'tabindex' => '-1',
        'id' => 'focusable-container-without-tabbable-children',
      ],
      '#markup' => 'No tabbable children here',
    ];

    $form['multiple_of_same_selector_1'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'multiple-of-same-selector-1',
        'class' => ['multiple-of-same-selector'],
      ],
    ];

    $form['multiple_of_same_selector_1']['inside_same_selector_container_1'] = [
      '#type' => 'textfield',
    ];

    $form['multiple_of_same_selector_2'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'multiple-of-same-selector-2',
        'class' => ['multiple-of-same-selector'],
      ],
    ];

    $form['multiple_of_same_selector_2']['inside_same_selector_container_2'] = [
      '#type' => 'textfield',
    ];

    $form['nothing_tabbable'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'nothing-tabbable',
      ],
      '#markup' => 'nothing tabbable',
    ];

    $form['nothing_tabbable']['nested'] = [
      '#type' => 'container',
      '#markup' => 'There are divs in here, but nothing tabbable',
    ];

    $form['focus_first_in_container'] = [
      '#type' => 'submit',
      '#value' => 'Focus the first item in a container',
      '#name' => 'focusFirstContainer',
      '#ajax' => [
        'callback' => '::focusFirstInContainer',
      ],
    ];
    $form['focus_first_in_form'] = [
      '#type' => 'submit',
      '#value' => 'Focus the first item in the form',
      '#name' => 'focusFirstForm',
      '#ajax' => [
        'callback' => '::focusFirstInForm',
      ],
    ];
    $form['uses_selector_with_multiple_matches'] = [
      '#type' => 'submit',
      '#value' => 'Uses selector with multiple matches',
      '#name' => 'SelectorMultipleMatches',
      '#ajax' => [
        'callback' => '::focusFirstSelectorMultipleMatch',
      ],
    ];
    $form['focusable_container_no_tabbable_children'] = [
      '#type' => 'submit',
      '#value' => 'Focusable container, no tabbable children',
      '#name' => 'focusableContainerNotTabbableChildren',
      '#ajax' => [
        'callback' => '::focusableContainerNotTabbableChildren',
      ],
    ];

    $form['selector_has_nothing_tabbable'] = [
      '#type' => 'submit',
      '#value' => 'Try to focus container with nothing tabbable',
      '#name' => 'SelectorNothingTabbable',
      '#ajax' => [
        'callback' => '::selectorHasNothingTabbable',
      ],
    ];

    $form['selector_does_not_exist'] = [
      '#type' => 'submit',
      '#value' => 'Call FocusFirst on selector that does not exist.',
      '#name' => 'SelectorNotExist',
      '#ajax' => [
        'callback' => '::selectorDoesNotExist',
      ],
    ];

    $form['#attached']['library'][] = 'ajax_test/focus.first';

    return $form;
  }

  /**
   * Callback for testing FocusFirstCommand on a container.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function selectorDoesNotExist() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('#selector-does-not-exist'));
  }

  /**
   * Callback for testing FocusFirstCommand on a container.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function selectorHasNothingTabbable() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('#nothing-tabbable'));
  }

  /**
   * Callback for testing FocusFirstCommand on a container.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function focusableContainerNotTabbableChildren() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('#focusable-container-without-tabbable-children'));
  }

  /**
   * Callback for testing FocusFirstCommand on a container.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function focusFirstSelectorMultipleMatch() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('.multiple-of-same-selector'));
  }

  /**
   * Callback for testing FocusFirstCommand on a container.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function focusFirstInContainer() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('#a-container'));
  }

  /**
   * Callback for testing FocusFirstCommand on a form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function focusFirstInForm() {
    $response = new AjaxResponse();
    return $response->addCommand(new FocusFirstCommand('#ajax-test-focus-first-command-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
