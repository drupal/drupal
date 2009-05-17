<?php

/**
 * @file
 * Provide views data and handlers for contact.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 */
function contact_views_data_alter(&$data) {
  $data['users']['contact'] = array(
    'field' => array(
      'title' => t('Link to contact page'),
      'help' => t('Provide a simple link to the user contact page.'),
      'handler' => 'views_handler_field_contact_link',
    ),
  );
}
