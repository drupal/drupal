<?php

/**
 * @file
 * Provide views data for dblog.module.
 */

/**
 * Implements hook_views_data().
 */
function dblog_views_data() {
  $data = array();

  $data['watchdog']['table']['group'] = t('Watchdog');
  $data['watchdog']['table']['wizard_id'] = 'watchdog';

  $data['watchdog']['table']['base'] = array(
    'field' => 'wid',
    'title' => t('Log entries'),
    'help' => t('Contains a list of log entries.'),
  );

  $data['watchdog']['wid'] = array(
    'title' => t('WID'),
    'help' => t('Unique watchdog event ID.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'search' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['uid'] = array(
    'title' => t('UID'),
    'help' => t('The user ID of the user on which the log entry was written..'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'search' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('User'),
      'help' => t('The user on which the log entry as written.'),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
    ),
  );

  $data['watchdog']['type'] = array(
    'title' => t('Type'),
    'help' => t('The of the log entry, for example "user" or "page not found.".'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'in_operator',
      'options callback' => '_dblog_get_message_types',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['message'] = array(
    'title' => t('Message'),
    'help' => t('The actual message of the log entry.'),
    'field' => array(
      'id' => 'dblog_message',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['variables'] = array(
    'title' => t('Variables'),
    'help' => t('The variables of the log entry in a serialized format.'),
    'field' => array(
      'id' => 'serialized',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['severity'] = array(
    'title' => t('Severity level'),
    'help' => t('The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).'),
    'field' => array(
      'id' => 'machine_name',
      'options callback' => 'Drupal\dblog\Controller\DbLogController::getLogLevelClassMap',
    ),
    'filter' => array(
      'id' => 'in_operator',
      'options callback' => 'Drupal\dblog\Controller\DbLogController::getLogLevelClassMap',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['link'] = array(
    'title' => t('Operations'),
    'help' => t('Operation links for the event.'),
    'field' => array(
      'id' => 'dblog_operations',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['location'] = array(
    'title' => t('Location'),
    'help' => t('URL of the origin of the event.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['referer'] = array(
    'title' => t('Referer'),
    'help' => t('URL of the previous page.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('Hostname of the user who triggered the event.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['watchdog']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('Date when the event occurred.'),
    'field' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
