<?php

declare(strict_types=1);

namespace Drupal\text_test\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hook implementations for text_test.
 */
class TextTestHooks {

  public function __construct(protected readonly StateInterface $state) {}

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle): void {
    if (($field_name = $this->state->get('field_test_constraint', FALSE)) && $entity_type->id() == 'node') {
      /** @var \Drupal\field\Entity\FieldConfig[] $fields */
      $fields[$field_name]->addConstraint('UniqueField');
    }
  }

}
