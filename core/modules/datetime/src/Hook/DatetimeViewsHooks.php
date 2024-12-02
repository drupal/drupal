<?php

namespace Drupal\datetime\Hook;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for datetime.
 */
class DatetimeViewsHooks {

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    return datetime_type_field_views_data_helper($field_storage, [], $field_storage->getMainPropertyName());
  }

}
