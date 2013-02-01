<?php

/**
 * @file
 * Fieldset design test.
 */

/**
 * Form constructor for testing theme_fieldset().
 */
function design_test_form_fieldset($form, &$form_state) {
  $form['regular'] = array(
    '#type' => 'fieldset',
    '#title' => 'Fieldset',
    '#description' => 'Fieldset description',
  );

  $form['collapsible'] = array(
    '#type' => 'details',
    '#title' => 'Collapsible details',
    '#description' => 'Details description',
    '#collapsed' => FALSE,
  );
  $form['collapsible']['fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => 'Fieldset title',
    '#description' => 'Fieldset description',
  );
  $form['collapsible']['fieldset']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield',
    '#description' => 'Textfield description',
    '#required' => TRUE,
  );
  $form['collapsible']['fieldset']['textarea'] = array(
    '#type' => 'textarea',
    '#title' => 'Textarea',
    '#description' => 'Textarea description',
  );

  $form['collapsed'] = $form['collapsible'];
  $form['collapsed']['#title'] = 'Collapsed details';
  $form['collapsed']['#collapsed'] = TRUE;

  $form['nested'] = $form['regular'];
  $form['nested']['#title'] = 'Parent fieldset';
  $form['nested']['nested'] = $form['nested'];
  $form['nested']['nested']['#title'] = 'Nested fieldset';

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

  return $form;
}

