<?php

/**
 * @file
 * Contains \Drupal\Core\Field\EntityReferenceFieldItemList.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a item list class for entity reference fields.
 */
class EntityReferenceFieldItemList extends FieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return array();
    }

    // Get a list of items having non-empty target ids.
    $list = array_filter($this->list, function($item) {
      return (bool) $item->target_id;
    });

    $ids = array();
    foreach ($list as $delta => $item) {
      $ids[$delta] = $item->target_id;
    }
    if (empty($ids)) {
      return array();
    }

    $target_type = $this->getFieldDefinition()->getSetting('target_type');
    $entities = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids);

    $target_entities = array();
    foreach ($ids as $delta => $target_id) {
      if (isset($entities[$target_id])) {
        $target_entities[$delta] = $entities[$target_id];
      }
    }

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = array();
      foreach ($default_value as $delta => $properties) {
        if (isset($properties['target_uuid'])) {
          $uuids[$delta] = $properties['target_uuid'];
        }
      }
      if ($uuids) {
        $target_type = $definition->getSetting('target_type');
        $entity_ids = \Drupal::entityQuery($target_type)
          ->condition('uuid', $uuids, 'IN')
          ->execute();
        $entities = \Drupal::entityManager()
          ->getStorage($target_type)
          ->loadMultiple($entity_ids);

        $entity_uuids = array();
        foreach ($entities as $id => $entity) {
          $entity_uuids[$entity->uuid()] = $id;
        }
        foreach ($uuids as $delta => $uuid) {
          if (isset($entity_uuids[$uuid])) {
            $default_value[$delta]['target_id'] = $entity_uuids[$uuid];
            unset($default_value[$delta]['target_uuid']);
          }
          else {
            unset($default_value[$delta]);
          }
        }
      }

      // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
      $default_value = array_values($default_value);
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = array();
    foreach ($default_value as $delta => $properties) {
      $ids[] = $properties['target_id'];
    }
    $entities = \Drupal::entityManager()
      ->getStorage($this->getSetting('target_type'))
      ->loadMultiple($ids);

    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['target_id']);
      $default_value[$delta]['target_uuid'] = $entities[$properties['target_id']]->uuid();
    }
    return $default_value;
  }

}
