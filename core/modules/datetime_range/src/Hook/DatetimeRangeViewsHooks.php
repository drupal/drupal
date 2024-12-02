<?php

namespace Drupal\datetime_range\Hook;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for datetime_range.
 */
class DatetimeRangeViewsHooks {

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    // Include datetime.views.inc file in order for helper function
    // datetime_type_field_views_data_helper() to be available.
    \Drupal::moduleHandler()->loadInclude('datetime', 'inc', 'datetime.views');
    // Get datetime field data for value and end_value.
    $data = datetime_type_field_views_data_helper($field_storage, [], 'value');
    $data = datetime_type_field_views_data_helper($field_storage, $data, 'end_value');
    return $data;
  }

}
