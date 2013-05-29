<?php

/**
 * @file
 * Details design test.
 */

/**
 * Form constructor for testing theme_details().
 */
function design_test_form_details($form, &$form_state) {
  $form['collapsible'] = array(
    '#type' => 'details',
    '#title' => 'Collapsible details',
    '#description' => 'Details description',
    '#collapsed' => FALSE,
    '#states' => array(
      'collapsed' => array(
        ':input[name="states-trigger"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['collapsed'] = array(
    '#type' => 'details',
    '#title' => 'Collapsed details',
    '#description' => 'Details description',
    '#collapsed' => TRUE,
  );
  $form['collapsed']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield',
    '#default_value' => '',
    '#description' => 'Textfield description',
    '#required' => TRUE,
  );
  $form['collapsed']['textarea'] = array(
    '#type' => 'textarea',
    '#title' => 'Textarea',
    '#default_value' => '',
    '#description' => 'Textarea description',
    '#required' => TRUE,
  );

  $form['collapsed2'] = array(
    '#type' => 'details',
    '#title' => 'Details',
    '#description' => 'Details description',
    '#collapsed' => TRUE,
  );
  $form['collapsed2']['collapsible'] = array(
    '#type' => 'details',
    '#title' => 'Collapsible details',
    '#description' => 'Details description',
    '#collapsed' => FALSE,
  );
  $form['collapsed2']['collapsed'] = array(
    '#type' => 'details',
    '#title' => 'Collapsed details',
    '#description' => 'Details description',
    '#collapsed' => TRUE,
  );
  $form['collapsed2']['regular'] = array(
    '#type' => 'details',
    '#title' => 'Details',
    '#description' => 'Details description',
  );

  $form['regular'] = array(
    '#type' => 'details',
    '#title' => 'Details',
    '#description' => 'Details description',
  );

  #$form['#attributes'] = array('class' => array('search-form'));
  $form['basic'] = array(
    '#type' => 'details',
    '#title' => 'Filter aliases',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['basic']['filter'] = array(
    '#type' => 'textfield',
    '#title' => '',
    '#default_value' => '',
    '#maxlength' => 128,
    '#size' => 25,
  );
  $form['basic']['actions'] = array(
    '#type' => 'actions',
  );
  $form['basic']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Filter',
  );
  $form['basic']['actions']['reset'] = array(
    '#type' => 'submit',
    '#value' => 'Reset',
  );

  $form['body'] = array(
    '#type' => 'text_format',
    '#title' => 'Body',
  );

  // Vertical tabs.
  // Replicate the entire form; some more black magic.
  $subform = array_diff_key($form, array('group' => 0, 'tabs' => 0));

  $form['group'] = array(
    '#type' => 'vertical_tabs',
  );
  $form['tabs'][0] = array(
    '#type' => 'details',
    '#title' => 'Vertical tab 1',
    '#description' => 'Description',
    '#group' => 'group',
  );
  $form['tabs'][0] += $subform;

  $form['tabs'][1] = array(
    '#type' => 'details',
    '#title' => 'Vertical tab 2',
    '#description' => '<p>Description</p><p>Description</p>',
    '#group' => 'group',
  );
  $form['tabs'][1] += $subform;

  // In case you didn't know, vertical tabs are supported recursively.
  $form['tabs'][0]['subgroup'] = array(
    '#type' => 'vertical_tabs',
  );

  $form['subtabs'][0] = array(
    '#type' => 'details',
    '#title' => 'Vertical tab 1',
    '#description' => 'Description',
    '#group' => 'subgroup',
  );
  $form['subtabs'][0] += $subform;

  $form['subtabs'][1] = array(
    '#type' => 'details',
    '#title' => 'Vertical tab 2',
    '#description' => '<p>Description</p><p>Description</p>',
    '#group' => 'subgroup',
  );
  $form['subtabs'][1] += $subform;


  // #states supports:
  // - a 'collapsed' state trigger
  // - a 'collapsed' remote condition
  $form['states-trigger'] = array(
    '#type' => 'checkbox',
    '#title' => 'Collapse first details',
    '#weight' => -100,
  );
  $form['states-condition'] = array(
    '#type' => 'checkbox',
    '#title' => 'Required if second details are collapsed.',
    '#states' => array(
      'required' => array(
        'details#edit-collapsed' => array('collapsed' => TRUE),
      ),
    ),
    '#weight' => -100,
  );

  return $form;
}

