<?php

/**
 * @file
 * Post update functions for test module.
 */

declare(strict_types=1);

// cspell:ignore postupdate

/**
 * First update.
 */
function update_test_postupdate_post_update_first(): string {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'First update';
}

/**
 * Second update.
 */
function update_test_postupdate_post_update_second(): string {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Second update';
}

/**
 * Test1 update.
 */
function update_test_postupdate_post_update_test1(): string {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Test1 update';
}

/**
 * Test0 update.
 */
function update_test_postupdate_post_update_test0(): string {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'Test0 update';
}

/**
 * Testing batch processing in post updates update.
 */
function update_test_postupdate_post_update_test_batch(&$sandbox = NULL): string {
  if (!isset($sandbox['steps'])) {
    $sandbox['current_step'] = 0;
    $sandbox['steps'] = 3;
  }

  $sandbox['current_step']++;

  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__ . '-' . $sandbox['current_step'];
  \Drupal::state()->set('post_update_test_execution', $execution);

  $sandbox['#finished'] = $sandbox['current_step'] / $sandbox['steps'];
  return 'Test post update batches';
}

/**
 * Implements hook_removed_post_updates().
 */
function update_test_postupdate_removed_post_updates(): array {
  return [
    'update_test_postupdate_post_update_foo' => '8.x-1.0',
    'update_test_postupdate_post_update_bar' => '8.x-2.0',
    'update_test_postupdate_post_update_pub' => '3.0.0',
    'update_test_postupdate_post_update_baz' => '3.0.0',
  ];
}
