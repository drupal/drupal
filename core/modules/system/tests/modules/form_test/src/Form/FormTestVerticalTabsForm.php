<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestVerticalTabsForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FormTestVerticalTabsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_vertical_tabs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['vertical_tabs'] = array(
      '#type' => 'vertical_tabs',
    );
    $form['tab1'] = array(
      '#type' => 'details',
      '#title' => t('Tab 1'),
      '#group' => 'vertical_tabs',
      '#access' => \Drupal::currentUser()->hasPermission('access vertical_tab_test tabs'),
    );
    $form['tab1']['field1'] = array(
      '#title' => t('Field 1'),
      '#type' => 'textfield',
    );
    $form['tab2'] = array(
      '#type' => 'details',
      '#title' => t('Tab 2'),
      '#group' => 'vertical_tabs',
      '#access' => \Drupal::currentUser()->hasPermission('access vertical_tab_test tabs'),
    );
    $form['tab2']['field2'] = array(
      '#title' => t('Field 2'),
      '#type' => 'textfield',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
