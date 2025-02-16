<?php

namespace Drupal\dblog\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for dblog.
 */
class DblogViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];
    $data['watchdog']['table']['group'] = $this->t('Watchdog');
    $data['watchdog']['table']['wizard_id'] = 'watchdog';
    $data['watchdog']['table']['base'] = [
      'field' => 'wid',
      'title' => $this->t('Log entries'),
      'help' => $this->t('Contains a list of log entries.'),
    ];
    $data['watchdog']['wid'] = [
      'title' => $this->t('WID'),
      'help' => $this->t('Unique watchdog event ID.'),
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
      'title' => $this->t('UID'),
      'help' => $this->t('The user ID of the user on which the log entry was written.'),
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
        'title' => $this->t('User'),
        'help' => $this->t('The user on which the log entry as written.'),
        'base' => 'users_field_data',
        'base field' => 'uid',
        'id' => 'standard',
      ],
    ];
    $data['watchdog']['type'] = [
      'title' => $this->t('Type'),
      'help' => $this->t('The type of the log entry, for example "user" or "page not found".'),
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
      'title' => $this->t('Message'),
      'help' => $this->t('The actual message of the log entry.'),
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
      'title' => $this->t('Variables'),
      'help' => $this->t('The variables of the log entry in a serialized format.'),
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
      'title' => $this->t('Severity level'),
      'help' => $this->t('The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).'),
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
      'title' => $this->t('Operations'),
      'help' => $this->t('Operation links for the event.'),
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
      'title' => $this->t('Location'),
      'help' => $this->t('URL of the origin of the event.'),
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
      'title' => $this->t('Referer'),
      'help' => $this->t('URL of the previous page.'),
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
      'title' => $this->t('Hostname'),
      'help' => $this->t('Hostname of the user who triggered the event.'),
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
      'title' => $this->t('Timestamp'),
      'help' => $this->t('Date when the event occurred.'),
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

}
