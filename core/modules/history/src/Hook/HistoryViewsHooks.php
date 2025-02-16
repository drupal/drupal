<?php

namespace Drupal\history\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for history.
 */
class HistoryViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    // History table
    // We're actually defining a specific instance of the table, so let's
    // alias it so that we can later add the real table for other purposes if we
    // need it.
    $data['history']['table']['group'] = $this->t('Content');
    // Explain how this table joins to others.
    $data['history']['table']['join'] = [
          // Directly links to node table.
      'node_field_data' => [
        'table' => 'history',
        'left_field' => 'nid',
        'field' => 'nid',
        'extra' => [
                  [
                    'field' => 'uid',
                    'value' => '***CURRENT_USER***',
                    'numeric' => TRUE,
                  ],
        ],
      ],
    ];
    $data['history']['timestamp'] = [
      'title' => $this->t('Has new content'),
      'field' => [
        'id' => 'history_user_timestamp',
        'help' => $this->t('Show a marker if the content is new or updated.'),
      ],
      'filter' => [
        'help' => $this->t('Show only content that is new or updated.'),
        'id' => 'history_user_timestamp',
      ],
    ];
    return $data;
  }

}
