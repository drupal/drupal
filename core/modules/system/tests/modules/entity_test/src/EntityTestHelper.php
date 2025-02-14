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

  /**
   * Creates a new bundle for entity_test entities.
   *
   * @param string $bundle
   *   The machine-readable name of the bundle.
   * @param string $text
   *   (optional) The human-readable name of the bundle. If none is provided, the
   *   machine name will be used.
   * @param string $entity_type
   *   (optional) The entity type for which the bundle is created. Defaults to
   *   'entity_test'.
   *
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityBundleInfo()
   */
  public static function createBundle($bundle, $text = NULL, $entity_type = 'entity_test'): void {
    $bundles = \Drupal::state()->get($entity_type . '.bundles', [$entity_type => ['label' => 'Entity Test Bundle']]);
    $bundles += [$bundle => ['label' => $text ?: $bundle]];
    \Drupal::state()->set($entity_type . '.bundles', $bundles);
    \Drupal::service('entity_bundle.listener')->onBundleCreate($bundle, $entity_type);
  }

  /**
   * Deletes a bundle for entity_test entities.
   *
   * @param string $bundle
   *   The machine-readable name of the bundle to delete.
   * @param string $entity_type
   *   (optional) The entity type for which the bundle is deleted. Defaults to
   *   'entity_test'.
   *
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityBundleInfo()
   */
  public static function deleteBundle($bundle, $entity_type = 'entity_test'): void {
    $bundles = \Drupal::state()->get($entity_type . '.bundles', [$entity_type => ['label' => 'Entity Test Bundle']]);
    unset($bundles[$bundle]);
    \Drupal::state()->set($entity_type . '.bundles', $bundles);
    \Drupal::service('entity_bundle.listener')->onBundleDelete($bundle, $entity_type);
  }

}
