<?php

declare(strict_types=1);

namespace Drupal\image_field_property_constraint_validation\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for image_field_property_constraint_validation.
 */
class ImagePropertyConstraintValidationHooks {

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle): void {
    if ($entity_type->id() == 'node' && !empty($fields['field_image'])) {
      /** @var \Drupal\field\Entity\FieldConfig[] $fields */
      $fields['field_image']->addPropertyConstraints('alt', ['AltTextContainsLlamas' => []]);
    }
  }

}
