<?php

declare(strict_types=1);

namespace Drupal\views_test_entity_reference\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_entity_reference.
 */
class ViewsTestEntityReferenceHooks {

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    $manager = \Drupal::entityTypeManager();
    $field_config_storage = $manager->getStorage('field_config');
    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $field_config_storage->loadByProperties(['field_type' => 'entity_reference']);
    foreach ($field_configs as $field_config) {
      $table_name = $field_config->getTargetEntityTypeId() . '__' . $field_config->getName();
      $column_name = $field_config->getName() . '_target_id';
      if (isset($data[$table_name][$column_name]['filter']['id'])
        && in_array($data[$table_name][$column_name]['filter']['id'], ['numeric', 'string'])) {
        $data[$table_name][$column_name]['filter']['id'] = 'entity_reference';
      }
    }
  }

}
