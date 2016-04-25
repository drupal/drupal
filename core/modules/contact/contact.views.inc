<?php

/**
 * @file
 * Provide views data for contact.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function contact_views_data_alter(&$data) {
  $data['users']['contact'] = array(
    'field' => array(
      'title' => t('Contact link'),
      'help' => t('Provide a simple link to the user contact page.'),
      'id' => 'contact_link',
    ),
  );
}
