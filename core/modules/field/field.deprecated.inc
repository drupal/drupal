<?php

/**
 * @file
 * This file contains functions marked as deprecated.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Field;

/**
 * Returns a lightweight map of fields across bundles.
 *
 * The function only returns non-deleted fields.
 *
 * @return
 *   An array keyed by entity type. Each value is an array which keys are
 *   field names and value is an array with two entries:
 *   - type: The field type.
 *   - bundles: The bundles in which the field appears.
 * Example:
 * @code
 * array(
 *   'node' => array(
 *     'body' => array(
 *       'bundles' => array(
 *         'page', 'article'
 *       ),
 *       'type' => 'text_with_summary',
 *     ),
 *   ),
 * );
 * @endcode
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFieldMap().
 */
function field_info_field_map() {
  return Field::fieldInfo()->getFieldMap();
}

/**
 * Returns data about an individual field.
 *
 * @param $entity_type
 *   The entity type.
 * @param $field_name
 *   The name of the field to retrieve. $field_name can only refer to a
 *   non-deleted field. For deleted fields, use field_info_field_by_id().
 *
 * @return
 *   The \Drupal\field\FieldConfigInterface field definition, as returned by
 *   entity_load_multiple_by_properties(), NULL if the field was not found.
 *
 * @see field_info_field_by_id()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getField($field_name).
 */
function field_info_field($entity_type, $field_name) {
  return Field::fieldInfo()->getField($entity_type, $field_name);
}

/**
 * Returns data about an individual field, given a field ID.
 *
 * @param $field_id
 *   The ID of the field to retrieve. $field_id can refer to a deleted field.
 *
 * @return
 *   The \Drupal\field\FieldConfigInterface field definition, as returned by
 *   entity_load_multiple_by_properties(), NULL if the field was not found.
 *
 * @see field_info_field()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFieldById($field_id).
 */
function field_info_field_by_id($field_id) {
  return Field::fieldInfo()->getFieldById($field_id);
}

/**
 * Returns the same data as field_info_field_by_id() for every field.
 *
 * Use of this function should be avoided when possible, since it loads and
 * statically caches a potentially large array of information.
 *
 * When iterating over the fields present in a given bundle after a call to
 * field_info_instances($entity_type, $bundle), it is recommended to use the
 * getField() method on each $instance instead.
 *
 * @return \Drupal\field\FieldConfigInterface[]
 *   An array of FieldConfigInterface configuration entities, keyed by UUID.
 *
 * @see field_info_field()
 * @see field_info_field_by_id()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFields().
 */
function field_info_field_by_ids() {
  return Field::fieldInfo()->getFields();
}

/**
 * Retrieves information about field instances.
 *
 * Use of this function to retrieve instances across separate bundles (i.e.
 * when the $bundle parameter is NULL) should be avoided when possible, since
 * it loads and statically caches a potentially large array of information.
 * Use field_info_field_map() instead.
 *
 * When retrieving the instances of a specific bundle (i.e. when both
 * $entity_type and $bundle_name are provided), the function also populates a
 * static cache with the corresponding field definitions, allowing fast
 * retrieval of field_info_field() later in the request.
 *
 * @param $entity_type
 *   (optional) The entity type for which to return instances.
 * @param $bundle_name
 *   (optional) The bundle name for which to return instances. If $entity_type
 *   is NULL, the $bundle_name parameter is ignored.
 *
 * @return
 *   If $entity_type is not set, return all instances keyed by entity type and
 *   bundle name. If $entity_type is set, return all instances for that entity
 *   type, keyed by bundle name. If $entity_type and $bundle_name are set,
 *   return all instances for that bundle.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal\field\Field::fieldInfo()->getInstances(),
 *   \Drupal\field\Field::fieldInfo()->getInstances($entity_type) or
 *   \Drupal\field\Field::fieldInfo()->getBundleInstances($entity_type, $bundle_name).
 */
function field_info_instances($entity_type = NULL, $bundle_name = NULL) {
  $cache = Field::fieldInfo();

  if (!isset($entity_type)) {
    return $cache->getInstances();
  }

  if (!isset($bundle_name)) {
    return $cache->getInstances($entity_type);
  }

  return $cache->getBundleInstances($entity_type, $bundle_name);
}

/**
 * Returns an array of instance data for a specific field and bundle.
 *
 * The function populates a static cache with all fields and instances used in
 * the bundle, allowing fast retrieval of field_info_field() or
 * field_info_instance() later in the request.
 *
 * @param $entity_type
 *   The entity type for the instance.
 * @param $field_name
 *   The field name for the instance.
 * @param $bundle_name
 *   The bundle name for the instance.
 *
 * @return
 *   An associative array of instance data for the specific field and bundle;
 *   NULL if the instance does not exist.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getInstance($entity_type, $bundle_name, $field_name).
 */
function field_info_instance($entity_type, $field_name, $bundle_name) {
  return Field::fieldInfo()->getInstance($entity_type, $bundle_name, $field_name);
}
