<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a method to create a body field for a given bundle.
 */
trait BodyFieldCreationTrait {

  /**
   * Creates a field of an body field storage on the specified bundle.
   *
   * @param string $entityType
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $fieldName
   *   (optional) The name of the field. Defaults to 'body'.
   * @param string $fieldLabel
   *   (optional) The label for the field. Defaults to 'Body'.
   * @param int $cardinality
   *   (optional) The cardinality of the field. Defaults to 1.
   */
  protected function createBodyField(string $entityType, string $bundle, string $fieldName = 'body', string $fieldLabel = 'Body', int $cardinality = 1): void {
    // Look for or add the specified field to the requested entity bundle.
    $fieldStorage = FieldStorageConfig::loadByName($entityType, $fieldName);
    if (!$fieldStorage) {
      FieldStorageConfig::create([
        'field_name' => $fieldName,
        'type' => 'text_long',
        'entity_type' => $entityType,
        'cardinality' => $cardinality,
        'persist_with_no_fields' => TRUE,
      ])->save();
      $fieldStorage = FieldStorageConfig::loadByName($entityType, $fieldName);
    }
    if (!FieldConfig::loadByName($entityType, $bundle, $fieldName)) {
      FieldConfig::create([
        'field_storage' => $fieldStorage,
        'bundle' => $bundle,
        'label' => $fieldLabel,
        'settings' => [
          'allowed_formats' => [],
        ],
      ])->save();

      /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
      $display_repository = \Drupal::service('entity_display.repository');

      // Assign widget settings for the default form mode.
      $display_repository->getFormDisplay($entityType, $bundle)
        ->setComponent('body', [
          'type' => 'text_textarea',
        ])
        ->save();

      // Assign display settings for the 'default' and 'teaser' view modes.
      $display_repository->getViewDisplay($entityType, $bundle)
        ->setComponent('body', [
          'label' => 'hidden',
          'type' => 'text_default',
        ])
        ->save();

      // The teaser view mode is created by the Standard profile and might
      // not exist.
      $view_modes = $display_repository->getViewModes($entityType);
      if (isset($view_modes['teaser'])) {
        $display_repository->getViewDisplay($entityType, $bundle, 'teaser')
          ->setComponent('body', [
            'label' => 'hidden',
            'type' => 'text_trimmed',
          ])
          ->save();
      }
    }
  }

}
