<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * @covers \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityHasFieldConstraintValidator
 *
 * @group Entity
 */
class EntityHasFieldConstraintValidatorTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_constraints'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_constraints');
    $this->createUser();
  }

  public function testValidation(): void {
    $this->state->set('entity_test_constraints.build', [
      'EntityHasField' => 'body',
    ]);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->clearCachedDefinitions();

    // Clear the typed data cache so that the entity has the correct constraints
    // during validation.
    $this->container->get('typed_data_manager')->clearCachedDefinitions();

    $storage = $entity_type_manager->getStorage('entity_test_constraints');

    /** @var \Drupal\entity_test\Entity\EntityTestConstraints $entity */
    $entity = $storage->create();
    // We should get a violation if we try to validate the entity before the
    // field has been created.
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The entity must have the body field.', $violations[0]->getMessage());
    $storage->save($entity);

    // Create the field.
    $field_storage = FieldStorageConfig::create([
      'type' => 'string',
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => 'body',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity->bundle(),
    ])->save();

    // Now that the field has been created, there should be no violations.
    $this->assertCount(0, $storage->loadUnchanged(1)->validate());
  }

}
