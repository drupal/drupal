<?php
/**
 * @file
 * Contains \Drupal\filter_test\Form\FilterTestFormatForm.
 */

namespace Drupal\filter_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Shows a test form for testing the 'text_format' form element.
 */
class FilterTestFormatForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filter_test_format_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This ensures that the parent array key makes it into the HTML ID of the
    // form elements.
    $form['#tree'] = TRUE;

    $form['all_formats'] = array(
      '#type' => 'details',
      '#title' => 'All text formats',
    );
    $form['all_formats']['no_default'] = array(
      '#type' => 'text_format',
      '#title' => 'No default value',
    );
    $form['all_formats']['default'] = array(
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'filter_test',
    );
    $form['all_formats']['default_missing'] = array(
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
    );

    $form['restricted_formats'] = array(
      '#type' => 'details',
      '#title' => 'Restricted text format list',
    );
    $form['restricted_formats']['no_default'] = array(
      '#type' => 'text_format',
      '#title' => 'No default value',
      '#allowed_formats' => array('full_html', 'filter_test'),
    );
    $form['restricted_formats']['default'] = array(
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'full_html',
      '#allowed_formats' => array('full_html', 'filter_test'),
    );
    $form['restricted_formats']['default_missing'] = array(
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
      '#allowed_formats' => array('full_html', 'filter_test'),
    );
    $form['restricted_formats']['default_disallowed'] = array(
      '#type' => 'text_format',
      '#title' => 'Disallowed default value',
      '#format' => 'filtered_html',
      '#allowed_formats' => array('full_html', 'filter_test'),
    );

    $form['single_format'] = array(
      '#type' => 'details',
      '#title' => 'Single text format',
    );
    $form['single_format']['no_default'] = array(
      '#type' => 'text_format',
      '#title' => 'No default value',
      '#allowed_formats' => array('filter_test'),
    );
    $form['single_format']['default'] = array(
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'filter_test',
      '#allowed_formats' => array('filter_test'),
    );
    $form['single_format']['default_missing'] = array(
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
      '#allowed_formats' => array('filter_test'),
    );
    $form['single_format']['default_disallowed'] = array(
      '#type' => 'text_format',
      '#title' => 'Disallowed default value',
      '#format' => 'full_html',
      '#allowed_formats' => array('filter_test'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
