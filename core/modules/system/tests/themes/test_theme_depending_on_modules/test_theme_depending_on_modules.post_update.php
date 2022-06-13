<?php

/**
 * @file
 * Test theme depending on modules post update hooks.
 */

if (\Drupal::state()->get('test_theme_depending_on_modules.post_update')) {

  /**
   * Install a dependent module.
   */
  function test_theme_depending_on_modules_post_update_module_install(&$sandbox = NULL) {
    \Drupal::service('module_installer')->install(['test_another_module_required_by_theme']);
    return 'Post update message from theme post update function';
  }

}

if (\Drupal::state()->get('test_theme_depending_on_modules.removed_post_updates')) {

  /**
   * Implements HOOK_removed_post_updates().
   */
  function test_theme_depending_on_modules_removed_post_updates() {
    return [
      'test_theme_depending_on_modules_post_update_foo' => '3.1',
    ];
  }

}
