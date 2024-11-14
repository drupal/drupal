<?php

declare(strict_types=1);

namespace Drupal\datetime_range_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for datetime_range_test.
 */
class DatetimeRangeTestHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Inhibit views data for the 'taxonomy_term' entity type in order to cover
    // the case when an entity type provides no views data.
    // @see https://www.drupal.org/project/drupal/issues/2995578
    // @see \Drupal\Tests\datetime_range\Kernel\Views\EntityTypeWithoutViewsDataTest
    $entity_types['taxonomy_term']->setHandlerClass('views_data', NULL);
  }

}
