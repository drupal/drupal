<?php

namespace Drupal\dblog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the database logging filter form.
 */
class DblogFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dblog_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $filters = dblog_filters();

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter log messages'),
      '#open' => TRUE,
    ];
    foreach ($filters as $key => $filter) {
      $form['filters']['status'][$key] = [
        '#title' => $filter['title'],
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 8,
        '#options' => $filter['options'],
      ];
      if (!empty($_SESSION['dblog_overview_filter'][$key])) {
        $form['filters']['status'][$key]['#default_value'] = $_SESSION['dblog_overview_filter'][$key];
      }
    }

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($_SESSION['dblog_overview_filter'])) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('type') && $form_state->isValueEmpty('severity')) {
      $form_state->setErrorByName('type', $this->t('You must select something to filter by.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = dblog_filters();
    foreach ($filters as $name => $filter) {
      if ($form_state->hasValue($name)) {
        $_SESSION['dblog_overview_filter'][$name] = $form_state->getValue($name);
      }
    }
  }

  /**
   * Resets the filter form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['dblog_overview_filter'] = [];
  }

}
