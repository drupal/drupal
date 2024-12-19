<?php

declare(strict_types=1);

namespace Drupal\node_display_configurable_test\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_display_configurable_test.
 */
class NodeDisplayConfigurableTestHooks {

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$base_field_definitions, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() == 'node') {
      foreach (['created', 'uid', 'title'] as $field) {
        /** @var \Drupal\Core\Field\BaseFieldDefinition[] $base_field_definitions */
        $base_field_definitions[$field]->setDisplayConfigurable('view', TRUE);
      }
    }
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    // Allow skipping of extra preprocessing for configurable display.
    $entity_types['node']->set('enable_base_field_custom_preprocess_skipping', TRUE);
    $entity_types['node']->set('enable_page_title_template', TRUE);
  }

}
