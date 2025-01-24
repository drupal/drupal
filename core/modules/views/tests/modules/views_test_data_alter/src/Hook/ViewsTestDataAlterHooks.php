<?php

declare(strict_types=1);

namespace Drupal\views_test_data_alter\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class ViewsTestDataAlterHooks {

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    // Modify a filter to use a different filter handler plugin
    // by default so that we can test that the handler used
    // respects the handler plugin ID specified in the
    // configuration.
    $data['node_field_data']['status']['filter']['id'] = 'numeric';
  }

}
