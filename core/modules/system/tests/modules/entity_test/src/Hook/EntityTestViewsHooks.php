<?php

declare(strict_types=1);

namespace Drupal\entity_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test.
 */
class EntityTestViewsHooks {

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $data['entity_test']['name_alias'] = $data['entity_test']['name'];
    $data['entity_test']['name_alias']['field']['real field'] = 'name';
  }

}
