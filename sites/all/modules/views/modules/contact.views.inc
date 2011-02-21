<?php
//$Id: contact.views.inc,v 1.1.6.2 2010/07/19 09:18:42 dereine Exp $
/**
 * @file
 * Provide views data and handlers for contact.module
 */

function contact_views_data() {
  $data['users']['contact'] = array(
    'field' => array(
      'title' => t('Link to contact page'),
      'help' => t('Provide a simple link to the user contact page.'),
      'handler' => 'views_handler_field_contact_link',
    ),
  );
  return $data;
}

