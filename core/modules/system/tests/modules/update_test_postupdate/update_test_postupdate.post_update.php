<?php

/**
 * First update.
 */
function update_test_postupdate_post_update_first() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'First update';
}

/**
 * Second update.
 */
function update_test_postupdate_post_update_second() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Second update';
}

/**
 * Test1 update.
 */
function update_test_postupdate_post_update_test1() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Test1 update';
}

/**
 * Test0 update.
 */
function update_test_postupdate_post_update_test0() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Test0 update';
}

/**
 * Testing batch processing in post updates update.
 */
function update_test_postupdate_post_update_test_batch(&$sandbox = NULL) {
  if (!isset($sandbox['steps'])) {
    $sandbox['current_step'] = 0;
    $sandbox['steps'] = 3;
  }

  $sandbox['current_step']++;

  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__ . '-' . $sandbox['current_step'];
  \Drupal::state()->set('post_update_test_execution', $execution);

  $sandbox['#finished'] = $sandbox['current_step'] /  $sandbox['steps'];
  return 'Test post update batches';
}
