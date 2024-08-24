<?php

declare(strict_types=1);

namespace Drupal\filter_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Shows a test form for testing the 'text_format' form element.
 *
 * @internal
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

    $form['all_formats'] = [
      '#type' => 'details',
      '#title' => 'All text formats',
    ];
    $form['all_formats']['no_default'] = [
      '#type' => 'text_format',
      '#title' => 'No default value',
    ];
    $form['all_formats']['default'] = [
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'filter_test',
    ];
    $form['all_formats']['default_missing'] = [
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
    ];

    $form['restricted_formats'] = [
      '#type' => 'details',
      '#title' => 'Restricted text format list',
    ];
    $form['restricted_formats']['no_default'] = [
      '#type' => 'text_format',
      '#title' => 'No default value',
      '#allowed_formats' => ['full_html', 'filter_test'],
    ];
    $form['restricted_formats']['default'] = [
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'full_html',
      '#allowed_formats' => ['full_html', 'filter_test'],
    ];
    $form['restricted_formats']['default_missing'] = [
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
      '#allowed_formats' => ['full_html', 'filter_test'],
    ];
    $form['restricted_formats']['default_disallowed'] = [
      '#type' => 'text_format',
      '#title' => 'Disallowed default value',
      '#format' => 'filtered_html',
      '#allowed_formats' => ['full_html', 'filter_test'],
    ];

    $form['single_format'] = [
      '#type' => 'details',
      '#title' => 'Single text format',
    ];
    $form['single_format']['no_default'] = [
      '#type' => 'text_format',
      '#title' => 'No default value',
      '#allowed_formats' => ['filter_test'],
    ];
    $form['single_format']['default'] = [
      '#type' => 'text_format',
      '#title' => 'Default value',
      '#format' => 'filter_test',
      '#allowed_formats' => ['filter_test'],
    ];
    $form['single_format']['default_missing'] = [
      '#type' => 'text_format',
      '#title' => 'Missing default value',
      '#format' => 'missing_format',
      '#allowed_formats' => ['filter_test'],
    ];
    $form['single_format']['default_disallowed'] = [
      '#type' => 'text_format',
      '#title' => 'Disallowed default value',
      '#format' => 'full_html',
      '#allowed_formats' => ['filter_test'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
