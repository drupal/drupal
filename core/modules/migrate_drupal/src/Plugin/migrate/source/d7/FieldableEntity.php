<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for D7 source plugins which need to collect field values.
 *
 * Field values are collected from the Field API.
 *
 * Refer to the existing implementations for examples:
 *
 * @see \Drupal\node\Plugin\migrate\source\d7\Node
 * @see \Drupal\user\Plugin\migrate\source\d7\User
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 */
abstract class FieldableEntity extends DrupalSqlBase {

  /**
   * Cached field and field instance definitions.
   *
   * @var array
   */
  protected $fieldInfo;

  /**
   * Returns all non-deleted field instances attached to a specific entity type.
   *
   * Typically, getFields() is used in the prepareRow method of a source plugin
   * to get a list of all the field instances of the entity. A source plugin can
   * then loop through the list of fields to do any other preparation before
   * processing the row. Typically, a source plugin will use getFieldValues()
   * to get the values of each field.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|null $bundle
   *   (optional) The bundle.
   *
   * @return array[]
   *   The field instances, keyed by field name.
   */
  protected function getFields($entity_type, $bundle = NULL) {
    $cid = $entity_type . ':' . ($bundle ?? '');
    if (!isset($this->fieldInfo[$cid])) {
      $query = $this->select('field_config_instance', 'fci')
        ->fields('fci')
        ->condition('fci.entity_type', $entity_type)
        ->condition('fci.bundle', $bundle ?? $entity_type)
        ->condition('fci.deleted', 0);

      // Join the 'field_config' table and add the 'translatable' setting to the
      // query.
      $query->leftJoin('field_config', 'fc', '[fci].[field_id] = [fc].[id]');
      $query->addField('fc', 'translatable');

      $this->fieldInfo[$cid] = $query->execute()->fetchAllAssoc('field_name');
    }

    return $this->fieldInfo[$cid];
  }

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * Typically, getFieldValues() is used in the prepareRow method of a source
   * plugin where the return values are placed on the row source.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param int $entity_id
   *   The entity ID.
   * @param int|null $revision_id
   *   (optional) The entity revision ID.
   * @param string $language
   *   (optional) The field language.
   *
   * @return array
   *   The raw field values, keyed and sorted by delta.
   */
  protected function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL, $language = NULL) {
    $table = (isset($revision_id) ? 'field_revision_' : 'field_data_') . $field;
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('deleted', 0)
      ->orderBy('delta');
    if (isset($revision_id)) {
      $query->condition('revision_id', $revision_id);
    }
    // Add 'language' as a query condition if it has been defined by Entity
    // Translation.
    if ($language) {
      $query->condition('language', $language);
    }
    $values = [];
    foreach ($query->execute() as $row) {
      foreach ($row as $key => $value) {
        $delta = $row['delta'];
        if (str_starts_with($key, $field)) {
          $column = substr($key, strlen($field) + 1);
          $values[$delta][$column] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * Checks if an entity type uses Entity Translation.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return bool
   *   Whether the entity type uses entity translation.
   */
  protected function isEntityTranslatable($entity_type) {
    return in_array($entity_type, $this->variableGet('entity_translation_entity_types', []), TRUE);
  }

  /**
   * Gets an entity source language from the 'entity_translation' table.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return string|bool
   *   The entity source language or FALSE if no source language was found.
   */
  protected function getEntityTranslationSourceLanguage($entity_type, $entity_id) {
    try {
      return $this->select('entity_translation', 'et')
        ->fields('et', ['language'])
        ->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity_id)
        ->condition('source', '')
        ->execute()
        ->fetchField();
    }
    // The table might not exist.
    catch (\Exception) {
      return FALSE;
    }
  }

}
