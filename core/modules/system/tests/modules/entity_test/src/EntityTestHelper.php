<?php

declare(strict_types=1);

namespace Drupal\entity_test;

/**
 * Class for the entity API providing several entity types for testing.
 */
class EntityTestHelper {

  /**
   * Returns a list of test entity types.
   *
   * The returned entity types are one for each available entity storage type:
   * - The plain entity_test type supports neither revisions nor multilingual
   *   properties.
   * - The entity_test_mul type supports multilingual properties.
   * - The entity_test_rev type supports revisions.
   * - The entity_test_mulrev type supports both revisions and multilingual
   *   properties.
   *
   * @param int $filter
   *   Either EntityTestTypesFilter::Revisable to only return revisable entity types or
   *   EntityTestTypesFilter::Multilingual to only return multilingual ones. Defaults
   *   to NULL, which returns all.
   *
   * @return array
   *   List with entity_types.
   */
  public static function getEntityTypes($filter = NULL): array {
    $types = [];
    if ($filter === NULL || $filter === EntityTestTypesFilter::Routing) {
      $types[] = 'entity_test';
    }
    if ($filter != EntityTestTypesFilter::Revisable) {
      $types[] = 'entity_test_mul';
      $types[] = 'entity_test_mul_langcode_key';
      $types[] = 'entity_test_mul_changed';
    }
    if ($filter != EntityTestTypesFilter::Multilingual) {
      $types[] = 'entity_test_rev';
    }
    if ($filter === EntityTestTypesFilter::Routing) {
      $types[] = 'entity_test_base_field_display';
      $types[] = 'entity_test_string_id';
      $types[] = 'entity_test_uuid_id';
      $types[] = 'entity_test_no_id';
      $types[] = 'entity_test_mul_with_bundle';
    }
    $types[] = 'entity_test_mulrev';
    $types[] = 'entity_test_mulrev_changed';

    return array_combine($types, $types);
  }

}
