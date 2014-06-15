<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\Field\FieldType\TaxonomyTermReferenceFieldItemList.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Represents a configurable taxonomy_term_reference entity field item list.
 */
class TaxonomyTermReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = array();
    foreach ($default_value as $delta => $properties) {
      $ids[] = $properties['target_id'];
    }
    $entities = \Drupal::entityManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($ids);

    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['target_id']);
      $default_value[$delta]['target_uuid'] = $entities[$properties['target_id']]->uuid();
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, ContentEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    // Convert UUIDs to numeric IDs.
    $uuids = array();
    foreach ($default_value as $delta => $properties) {
      $uuids[$delta] = $properties['target_uuid'];
    }
    if ($uuids) {
      $entity_ids = \Drupal::entityQuery('taxonomy_term')
        ->condition('uuid', $uuids, 'IN')
        ->execute();
      $entities = \Drupal::entityManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple($entity_ids);

      foreach ($entities as $id => $entity) {
        $entity_ids[$entity->uuid()] = $id;
      }
      foreach ($uuids as $delta => $uuid) {
        if (isset($entity_ids[$uuid])) {
          $default_value[$delta]['target_id'] = $entity_ids[$uuid];
          unset($default_value[$delta]['target_uuid']);
        }
        else {
          unset($default_value[$delta]);
        }
      }
    }

    // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
    $default_value = array_values($default_value);

    return $default_value;
  }

}
