<?php

namespace Drupal\datetime_range\Hook;

use Drupal\datetime\DateTimeViewsHelper;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for datetime_range.
 */
class DatetimeRangeViewsHooks {

  public function __construct(
    protected readonly DateTimeViewsHelper $dateTimeViewsHelper,
  ) {}

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    // Get datetime field data for value and end_value.
    $data = $this->dateTimeViewsHelper->buildViewsData($field_storage, [], 'value');
    $data = $this->dateTimeViewsHelper->buildViewsData($field_storage, $data, 'end_value');
    return $data;
  }

}
