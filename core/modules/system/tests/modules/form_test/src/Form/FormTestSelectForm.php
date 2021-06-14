<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a form to test #type 'select' validation.
 *
 * @internal
 */
class FormTestSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base = [
      '#type' => 'select',
      '#options' => ['one' => 'one', 'two' => 'two', 'three' => 'three', 'four' => '<strong>four</strong>'],
    ];

    $form['select'] = $base + [
      '#title' => '#default_value one',
      '#default_value' => 'one',
    ];
    $form['select_required'] = $base + [
      '#title' => '#default_value one, #required',
      '#required' => TRUE,
      '#default_value' => 'one',
    ];
    $form['select_optional'] = $base + [
      '#title' => '#default_value one, not #required',
      '#required' => FALSE,
      '#default_value' => 'one',
    ];
    $form['empty_value'] = $base + [
      '#title' => '#default_value one, #required, #empty_value 0',
      '#required' => TRUE,
      '#default_value' => 'one',
      '#empty_value' => 0,
    ];
    $form['empty_value_one'] = $base + [
      '#title' => '#default_value = #empty_value, #required',
      '#required' => TRUE,
      '#default_value' => 'one',
      '#empty_value' => 'one',
    ];

    $form['no_default'] = $base + [
      '#title' => 'No #default_value, #required',
      '#required' => TRUE,
    ];
    $form['no_default_optional'] = $base + [
      '#title' => 'No #default_value, not #required',
      '#required' => FALSE,
      '#description' => 'Should result in "one" because it is not required and there is no #empty_value requested, so default browser behavior of preselecting first option is in effect.',
    ];
    $form['no_default_optional_empty_value'] = $base + [
      '#title' => 'No #default_value, not #required, #empty_value empty string',
      '#empty_value' => '',
      '#required' => FALSE,
      '#description' => 'Should result in an empty string (due to #empty_value) because it is optional.',
    ];

    $form['no_default_empty_option'] = $base + [
      '#title' => 'No #default_value, #required, #empty_option',
      '#required' => TRUE,
      '#empty_option' => '- Choose -',
    ];
    $form['no_default_empty_option_optional'] = $base + [
      '#title' => 'No #default_value, not #required, #empty_option',
      '#empty_option' => '- Dismiss -',
      '#description' => 'Should result in an empty string (default of #empty_value) because it is optional.',
    ];

    $form['no_default_empty_value'] = $base + [
      '#title' => 'No #default_value, #required, #empty_value 0',
      '#required' => TRUE,
      '#empty_value' => 0,
      '#description' => 'Should never result in 0.',
    ];
    $form['no_default_empty_value_one'] = $base + [
      '#title' => 'No #default_value, #required, #empty_value one',
      '#required' => TRUE,
      '#empty_value' => 'one',
      '#description' => 'A mistakenly assigned #empty_value contained in #options should not be valid.',
    ];
    $form['no_default_empty_value_optional'] = $base + [
      '#title' => 'No #default_value, not #required, #empty_value 0',
      '#required' => FALSE,
      '#empty_value' => 0,
      '#description' => 'Should result in 0 because it is optional.',
    ];

    $form['multiple'] = $base + [
      '#title' => '#multiple, #default_value two',
      '#default_value' => ['two'],
      '#multiple' => TRUE,
    ];
    $form['multiple_no_default'] = $base + [
      '#title' => '#multiple, no #default_value',
      '#multiple' => TRUE,
    ];
    $form['multiple_no_default_required'] = $base + [
      '#title' => '#multiple, #required, no #default_value',
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];

    $form['opt_groups'] = [
      '#type' => 'select',
      '#options' => [
        'optgroup_one' => ['one' => 'one', 'two' => 'two', 'three' => 'three', 'four' => '<strong>four</strong>'],
        'optgroup_two' => ['five' => 'five', 'six' => 'six'],
      ],
    ];

    // Add a select that should have its options left alone.
    $form['unsorted'] = [
      '#type' => 'select',
      '#options' => $this->makeSortableOptions('uso'),
    ];

    // Add a select to test sorting at the top level, and with some of the
    // option groups sorted, some left alone, and at least one with #sort_start
    // set to a non-default value.
    $sortable_options = $this->makeSortableOptions('sso');
    $sortable_options['sso_zzgroup']['#sort_options'] = TRUE;
    $sortable_options['sso_xxgroup']['#sort_options'] = TRUE;
    $sortable_options['sso_xxgroup']['#sort_start'] = 1;
    // Do not use a sort start on this one.
    $form['sorted'] = [
      '#type' => 'select',
      '#options' => $sortable_options,
      '#sort_options' => TRUE,
    ];

    // Add a select to test sorting with a -NONE- option included,
    // and #sort_start set.
    $sortable_none_options = $this->makeSortableOptions('sno');
    $sortable_none_options['sno_zzgroup']['#sort_options'] = TRUE;
    $form['sorted_none'] = [
      '#type' => 'select',
      '#options' => $sortable_none_options,
      '#sort_options' => TRUE,
      '#sort_start' => 4,
      '#empty_value' => 'sno_empty',
    ];

    // Add a select to test sorting with a -NONE- option included,
    // and #sort_start not set.
    $sortable_none_nostart_options = $this->makeSortableOptions('snn');
    $sortable_none_nostart_options['snn_zzgroup']['#sort_options'] = TRUE;
    $form['sorted_none_nostart'] = [
      '#type' => 'select',
      '#options' => $sortable_none_nostart_options,
      '#sort_options' => TRUE,
      '#empty_value' => 'snn_empty',
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];
    return $form;
  }

  /**
   * Makes and returns a set of options to test sorting on.
   *
   * @param string $prefix
   *   Prefix for the keys of the options.
   *
   * @return array
   *   Options array, including option groups, for testing.
   */
  protected function makeSortableOptions($prefix) {
    return [
      // Don't use $this->t() here, to avoid adding strings to
      // localize.drupal.org. Do use TranslatableMarkup in places, to test
      // that labels are cast to strings before sorting.
      $prefix . '_first_element' => new TranslatableMarkup('first element'),
      $prefix . '_second' => new TranslatableMarkup('second element'),
      $prefix . '_zzgroup' => [
        $prefix . '_gc' => new TranslatableMarkup('group c'),
        $prefix . '_ga' => new TranslatableMarkup('group a'),
        $prefix . '_gb' => 'group b',
      ],
      $prefix . '_yygroup' => [
        $prefix . '_ge' => new TranslatableMarkup('group e'),
        $prefix . '_gd' => new TranslatableMarkup('group d'),
        $prefix . '_gf' => new TranslatableMarkup('group f'),
      ],
      $prefix . '_xxgroup' => [
        $prefix . '_gz' => new TranslatableMarkup('group z'),
        $prefix . '_gi' => new TranslatableMarkup('group i'),
        $prefix . '_gh' => new TranslatableMarkup('group h'),
      ],
      $prefix . '_d' => 'd',
      $prefix . '_c' => new TranslatableMarkup('main c'),
      $prefix . '_b' => new TranslatableMarkup('main b'),
      $prefix . '_a' => 'a',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
