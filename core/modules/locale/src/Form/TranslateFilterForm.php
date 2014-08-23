<?php

/**
 * @file
 * Contains \Drupal\locale\Form\TranslateFilterForm.
 */

namespace Drupal\locale\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filtered translation edit form.
 */
class TranslateFilterForm extends TranslateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translate_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $filters = $this->translateFilters();
    $filter_values = $this->translateFilterValues();

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';

    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter translatable strings'),
      '#open' => TRUE,
    );
    foreach ($filters as $key => $filter) {
      // Special case for 'string' filter.
      if ($key == 'string') {
        $form['filters']['status']['string'] = array(
          '#type' => 'search',
          '#title' => $filter['title'],
          '#description' => $filter['description'],
          '#default_value' => $filter_values[$key],
        );
      }
      else {
        $empty_option = isset($filter['options'][$filter['default']]) ? $filter['options'][$filter['default']] : '- None -';
        $form['filters']['status'][$key] = array(
          '#title' => $filter['title'],
          '#type' => 'select',
          '#empty_value' => $filter['default'],
          '#empty_option' => $empty_option,
          '#size' => 0,
          '#options' => $filter['options'],
          '#default_value' => $filter_values[$key],
        );
        if (isset($filter['states'])) {
          $form['filters']['status'][$key]['#states'] = $filter['states'];
        }
      }
    }

    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    );
    if (!empty($_SESSION['locale_translate_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => array('::resetForm'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = $this->translateFilters();
    foreach ($filters as $name => $filter) {
      if ($form_state->hasValue($name)) {
        $_SESSION['locale_translate_filter'][$name] = $form_state->getValue($name);
      }
    }
    $form_state->setRedirect('locale.translate_page');
  }

  /**
   * Provides a submit handler for the reset button.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['locale_translate_filter'] = array();
    $form_state->setRedirect('locale.translate_page');
  }

}
