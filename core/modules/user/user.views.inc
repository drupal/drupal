<?php

/**
 * @file
 * Provide views data for user.module.
 */

/**
 * Implements hook_views_plugins_argument_validator_alter().
 */
function user_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:user']['title'] = t('User ID');
  $plugins['entity:user']['class'] = 'Drupal\user\Plugin\views\argument_validator\User';
  $plugins['entity:user']['provider'] = 'user';
}
