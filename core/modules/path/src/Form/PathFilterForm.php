<?php

/**
 * @file
 * Contains \Drupal\path\Form\PathFilterForm.
 */

namespace Drupal\path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Provides the path admin overview filter form.
 */
class PathFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_admin_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $keys = NULL) {
    $form['#attributes'] = array('class' => array('search-form'));
    $form['basic'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter aliases'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['basic']['filter'] = array(
      '#type' => 'search',
      '#title' => 'Path alias',
      '#title_display' => 'invisible',
      '#default_value' => $keys,
      '#maxlength' => 128,
      '#size' => 25,
    );
    $form['basic']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    );
    if ($keys) {
      $form['basic']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => array(array($this, 'resetForm')),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect_route'] = new Url('path.admin_overview_filter', array(
      'keys' => trim($form_state['values']['filter']),
    ));
  }

  /**
   * Resets the filter selections.
   */
  public function resetForm(array &$form, array &$form_state) {
    $form_state['redirect_route'] = new Url('path.admin_overview');
  }

}
