<?php

declare(strict_types=1);

namespace Drupal\field_test;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Helper class for testing fields.
 */
class FieldTestHelper {

  /**
   * Helper function to enable entity translations.
   */
  public static function entityInfoTranslatable($entity_type_id = NULL, $translatable = NULL): array {
    static $stored_value = [];
    if (isset($entity_type_id)) {
      $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
      $entity_type = $entity_definition_update_manager->getEntityType($entity_type_id);
      $stored_value[$entity_type_id] = $translatable;
      if ($translatable != $entity_type->isTranslatable()) {
        $entity_definition_update_manager->uninstallEntityType($entity_type);
        $entity_type->set('translatable', $translatable);
        $entity_definition_update_manager->installEntityType($entity_type);
      }
    }
    return $stored_value;
  }

  /**
   * Sample 'default value' callback.
   */
  public static function defaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition): array {
    return [['value' => 99]];
  }

  /**
   * Store and retrieve keyed data for later verification by unit tests.
   *
   * This function is a simple in-memory key-value store with the
   * distinction that it stores all values for a given key instead of
   * just the most recently set value. field_test module hooks call
   * this function to record their arguments, keyed by hook name. The
   * unit tests later call this function to verify that the correct
   * hooks were called and were passed the correct arguments.
   *
   * This function ignores all calls until the first time it is called
   * with $key of NULL. Each time it is called with $key of NULL, it
   * erases all previously stored data from its internal cache, but also
   * returns the previously stored data to the caller. A typical usage
   * scenario is:
   *
   * @code
   *   // calls to FieldTestHelper::memorize() here are ignored
   *
   *   // turn on memorization
   *   FieldTestHelper::memorize();
   *
   *   // call some Field API functions that invoke field_test hooks
   *   FieldStorageConfig::create($field_definition)->save();
   *
   *   // retrieve and reset the memorized hook call data
   *   $mem = FieldTestHelper::memorize();
   *
   *   // make sure hook_field_storage_config_create() is invoked correctly
   *   assertEquals(1, count($mem['fieldStorageConfigCreate']));
   *   assertEquals([$field], $mem['fieldStorageConfigCreate'][0]);
   * @endcode
   *
   * @param string $key
   *   The key under which to store to $value, or NULL as described above.
   * @param mixed|null $value
   *   A value to store for $key.
   *
   * @return array|null
   *   An array mapping each $key to an array of each $value passed in
   *   for that key.
   */
  public static function memorize($key = NULL, $value = NULL): array|null {
    static $memorize;

    if (!isset($key)) {
      $return = $memorize;
      $memorize = [];
      return $return;
    }
    if (is_array($memorize)) {
      $memorize[$key][] = $value;
    }

    return NULL;
  }

}
