<?php

declare(strict_types=1);

namespace Drupal\link_test_base_field\Hook;

use Drupal\link\LinkItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for link_test_base_field.
 */
class LinkTestBaseFieldHooks {

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    $fields = [];
    if ($entity_type->id() === 'entity_test') {
      $fields['links'] = BaseFieldDefinition::create('link')->setLabel('Links')->setRevisionable(TRUE)->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)->setDescription('Add links to the entity.')->setRequired(FALSE)->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_REQUIRED,
      ])->setDisplayOptions('form', ['type' => 'link_default', 'weight' => 49]);
    }
    return $fields;
  }

}
