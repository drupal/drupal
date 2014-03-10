<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldType\ConfigurableEntityReferenceFieldItemList.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity_reference entity field.
 */
class ConfigurableEntityReferenceFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultValue() {
    $default_value = parent::getDefaultValue();

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = array();
      $fixed = array();
      foreach ($default_value as $delta => $properties) {
        if ($properties['target_uuid'] == 'anonymous' || $properties['target_uuid'] == 'administrator') {
          $fixed[$delta] = ($properties['target_uuid'] == 'anonymous') ? '0' : '1';
        }
        else {
          $uuids[$delta] = $properties['target_uuid'];
        }
      }
      if ($uuids) {
        $target_type = $this->getSetting('target_type');
        $entity_ids = \Drupal::entityQuery($target_type)
          ->condition('uuid', $uuids, 'IN')
          ->execute();
        $entities = \Drupal::entityManager()
          ->getStorageController($target_type)
          ->loadMultiple($entity_ids);

        foreach ($entities as $id => $entity) {
          $entity_ids[$entity->uuid()] = $id;
        }
        foreach ($uuids as $delta => $uuid) {
          if ($entity_ids[$uuid]) {
            $default_value[$delta]['target_id'] = $entity_ids[$uuid];
            unset($default_value[$delta]['target_uuid']);
          }
          else {
            unset($default_value[$delta]);
          }
        }
      }

      if ($fixed) {
        foreach ($fixed as $delta => $id) {
          $default_value[$delta]['target_id'] = $id;
          unset($default_value[$delta]['target_uuid']);
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
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = array();
    foreach ($default_value as $delta => $properties) {
      $ids[] = $properties['target_id'];
    }
    $entities = \Drupal::entityManager()
      ->getStorageController($this->getSetting('target_type'))
      ->loadMultiple($ids);

    foreach ($default_value as $delta => $properties) {
      $uuid = $entities[$properties['target_id']]->uuid();
      // @todo Some entities do not have uuid. IE: Anonymous and admin user.
      //   Remove this special case once http://drupal.org/node/2050843
      //   has been fixed.
      if (!$uuid) {
        $uuid = ($properties['target_id'] == '0') ? 'anonymous' : 'administrator';
      }
      unset($default_value[$delta]['target_id']);
      $default_value[$delta]['target_uuid'] = $uuid;
    }
    return $default_value;
  }

}
