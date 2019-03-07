<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityDefinitionUpdateManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityDefinitionUpdateManager
 * @group Entity
 * @group legacy
 */
class EntityDefinitionUpdateManagerTest extends UnitTestCase {

  /**
   * @expectedDeprecation Unsilenced deprecation: EntityDefinitionUpdateManagerInterface::applyUpdates() is deprecated in 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface::getChangeList() and execute each entity type and field storage update manually instead. See https://www.drupal.org/node/3034742.
   */
  public function testDeprecatedApplyUpdates() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class)->reveal();
    $entity_last_installed_schema_repository = $this->prophesize(EntityLastInstalledSchemaRepositoryInterface::class)->reveal();
    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class)->reveal();
    $entity_type_listener = $this->prophesize(EntityTypeListenerInterface::class)->reveal();
    $field_storage_definition_listener = $this->prophesize(FieldStorageDefinitionListenerInterface::class)->reveal();

    $entity_definition_update_manager = new EntityDefinitionUpdateManager($entity_type_manager, $entity_last_installed_schema_repository, $entity_field_manager, $entity_type_listener, $field_storage_definition_listener);

    $this->assertNull($entity_definition_update_manager->applyUpdates());
  }

}
