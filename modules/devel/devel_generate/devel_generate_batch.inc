<?php

/**
 * Devel Generate batch handling functions using the BatchAPI
 */

/**
 * Functions called from FAPI:
 */

function devel_generate_batch_content($form_state) {
  $operations = array();

  // Setup the batch operations and save the variables.
  $operations[] = array('devel_generate_batch_content_pre_node', array($form_state['values']));

  // add the kill operation
  if ($form_state['values']['kill_content']) {
    $operations[] = array('devel_generate_batch_content_kill', array());
  }

  // add the operations to create the nodes
  for ($num = 0; $num < $form_state['values']['num_nodes']; $num ++) {
    $operations[] = array('devel_generate_batch_content_add_node', array());
  }

  // start the batch
  $batch = array(
    'title' => t('Generating Content'),
    'operations' => $operations,
    'finished' => 'devel_generate_batch_finished',
    'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate_batch.inc',
  );
  batch_set($batch);
}

/**
 * Create Content Batch Functions:
 */

function devel_generate_batch_content_kill(&$context) {
  module_load_include('inc', 'devel_generate', 'devel_generate');
  devel_generate_content_kill($context['results']);
}

function devel_generate_batch_content_pre_node($vars, &$context) {
  $context['results'] = $vars;
  $context['results']['num_nids'] = 0;
  module_load_include('inc', 'devel_generate', 'devel_generate');
  devel_generate_content_pre_node($context['results']);
}

function devel_generate_batch_content_add_node(&$context) {
  module_load_include('inc', 'devel_generate', 'devel_generate');
  devel_generate_content_add_node($context['results']);
  $context['results']['num_nids'] ++;
}

function devel_generate_batch_finished($success, $results, $operations) {
  if ($success) {
    $message = t('Finished @num_nids nodes created successfully.', array('@num_nids' => $results['num_nids']));
  }
  else {
    $message = t('Finished with an error.');
  }
  drupal_set_message($message);
}

