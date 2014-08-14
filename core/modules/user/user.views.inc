<?php

/**
 * @file
 * Provide views data for user.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function user_views_data_alter(&$data) {
  $data['users']['user_bulk_form'] = array(
    'title' => t('Bulk update'),
    'help' => t('Add a form element that lets you run operations on multiple users.'),
    'field' => array(
      'id' => 'user_bulk_form',
    ),
  );
}

/**
 * Implements hook_views_plugins_argument_validator_alter().
 */
function user_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:user']['title'] = t('User ID');
  $plugins['entity:user']['class'] = 'Drupal\user\Plugin\views\argument_validator\User';
  $plugins['entity:user']['provider'] = 'user';
}
