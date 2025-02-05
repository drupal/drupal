<?php

declare(strict_types=1);

namespace Drupal\views_ui_test_field\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_ui_test_field.
 */
class ViewsUiTestFieldViewsHooks {

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data['views']['views_test_field_1'] = [
      'title' => 'Views test field 1 - FIELD_1_TITLE',
      'help' => 'Field 1 for testing purposes - FIELD_1_DESCRIPTION',
      'field' => [
        'id' => 'views_test_field_1',
      ],
    ];
    $data['views']['views_test_field_2'] = [
      'title' => 'Views test field 2 - FIELD_2_TITLE',
      'help' => 'Field 2 for testing purposes - FIELD_2_DESCRIPTION',
      'field' => [
        'id' => 'views_test_field_2',
      ],
    ];
    return $data;
  }

}
