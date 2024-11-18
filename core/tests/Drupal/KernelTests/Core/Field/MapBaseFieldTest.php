<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test_update\Entity\EntityTestUpdate;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests map base fields.
 *
 * @group Field
 */
class MapBaseFieldTest extends EntityKernelTestBase {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityTypeManager->getDefinitions(), array_flip(['user', 'entity_test'])) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Tests uninstalling map item base field.
   */
  public function testUninstallMapItemBaseField(): void {
    $definitions['data_map'] = BaseFieldDefinition::create('map')
      ->setLabel('Data')
      ->setRequired(TRUE);

    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);

    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('data_map', 'entity_test_update', 'entity_test', $definitions['data_map']);

    $entity = EntityTestUpdate::create([
      'data_map' => [
        'key' => 'value',
      ],
    ]);
    $entity->save();

    $this->entityDefinitionUpdateManager->uninstallFieldStorageDefinition($definitions['data_map']);
  }

}
