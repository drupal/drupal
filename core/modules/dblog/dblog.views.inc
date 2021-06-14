<?php

/**
 * @file
 * Provide views data for dblog.module.
 */

/**
 * Implements hook_views_data().
 */
function dblog_views_data() {
  $data = [];

  $data['watchdog']['table']['group'] = t('Watchdog');
  $data['watchdog']['table']['wizard_id'] = 'watchdog';

  $data['watchdog']['table']['base'] = [
    'field' => 'wid',
    'title' => t('Log entries'),
    'help' => t('Contains a list of log entries.'),
  ];

  $data['watchdog']['wid'] = [
    'title' => t('WID'),
    'help' => t('Unique watchdog event ID.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['uid'] = [
    'title' => t('UID'),
    'help' => t('The user ID of the user on which the log entry was written.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('User'),
      'help' => t('The user on which the log entry as written.'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['type'] = [
    'title' => t('Type'),
    'help' => t('The type of the log entry, for example "user" or "page not found".'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'dblog_types',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['message'] = [
    'title' => t('Message'),
    'help' => t('The actual message of the log entry.'),
    'field' => [
      'id' => 'dblog_message',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['variables'] = [
    'title' => t('Variables'),
    'help' => t('The variables of the log entry in a serialized format.'),
    'field' => [
      'id' => 'serialized',
      'click sortable' => FALSE,
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['severity'] = [
    'title' => t('Severity level'),
    'help' => t('The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).'),
    'field' => [
      'id' => 'machine_name',
      'options callback' => 'Drupal\dblog\Controller\DbLogController::getLogLevelClassMap',
    ],
    'filter' => [
      'id' => 'in_operator',
      'options callback' => 'Drupal\Core\Logger\RfcLogLevel::getLevels',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['link'] = [
    'title' => t('Operations'),
    'help' => t('Operation links for the event.'),
    'field' => [
      'id' => 'dblog_operations',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['location'] = [
    'title' => t('Location'),
    'help' => t('URL of the origin of the event.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['referer'] = [
    'title' => t('Referer'),
    'help' => t('URL of the previous page.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['hostname'] = [
    'title' => t('Hostname'),
    'help' => t('Hostname of the user who triggered the event.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['watchdog']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Date when the event occurred.'),
    'field' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  return $data;
}
