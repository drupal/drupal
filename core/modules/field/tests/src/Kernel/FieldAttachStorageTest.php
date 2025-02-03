<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_test\FieldTestHelper;

/**
 * Tests storage-related Field Attach API functions.
 *
 * @group field
 * @todo move this to the Entity module
 */
class FieldAttachStorageTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Check field values insert, update and load.
   *
   * Works independently of the underlying field storage backend. Inserts or
   * updates random field data and then loads and verifies the data.
   */
  public function testFieldAttachSaveLoad(): void {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('', $entity_type);
    $cardinality = $this->fieldTestData->field_storage->getCardinality();

    // @todo Test empty values filtering and "compression" (store consecutive deltas).
    // Preparation: create three revisions and store them in $revision array.
    $values = [];
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    for ($revision_id = 0; $revision_id < 3; $revision_id++) {
      // Note: we try to insert one extra value.
      $current_values = $this->_generateTestFieldValues($cardinality + 1);
      $entity->{$this->fieldTestData->field_name}->setValue($current_values);
      $entity->setNewRevision();
      $entity->save();
      $entity_id = $entity->id();
      $current_revision = $entity->getRevisionId();
      $values[$current_revision] = $current_values;
    }

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
    $storage->resetCache();
    $entity = $storage->load($entity_id);
    // Confirm current revision loads the correct data.
    // Number of values per field loaded equals the field cardinality.
    $this->assertCount($cardinality, $entity->{$this->fieldTestData->field_name}, 'Current revision: expected number of values');
    for ($delta = 0; $delta < $cardinality; $delta++) {
      // The field value loaded matches the one inserted or updated.
      $this->assertEquals($values[$current_revision][$delta]['value'], $entity->{$this->fieldTestData->field_name}[$delta]->value, "Current revision: expected value $delta was found.");
    }

    // Confirm each revision loads the correct data.
    foreach (array_keys($values) as $revision_id) {
      $entity = $storage->loadRevision($revision_id);
      // Number of values per field loaded equals the field cardinality.
      $this->assertCount($cardinality, $entity->{$this->fieldTestData->field_name}, "Revision $revision_id: expected number of values.");
      for ($delta = 0; $delta < $cardinality; $delta++) {
        // The field value loaded matches the one inserted or updated.
        $this->assertEquals($values[$revision_id][$delta]['value'], $entity->{$this->fieldTestData->field_name}[$delta]->value, "Revision $revision_id: expected value $delta was found.");
      }
    }
  }

  /**
   * Tests the 'multiple' load feature.
   */
  public function testFieldAttachLoadMultiple(): void {
    $entity_type = 'entity_test_rev';

    // Define 2 bundles.
    $bundles = [
      1 => 'test_bundle_1',
      2 => 'test_bundle_2',
    ];
    EntityTestHelper::createBundle($bundles[1], entity_type: $entity_type);
    EntityTestHelper::createBundle($bundles[2], entity_type: $entity_type);
    // Define 3 fields:
    // - field_1 is in bundle_1 and bundle_2,
    // - field_2 is in bundle_1,
    // - field_3 is in bundle_2.
    $field_bundles_map = [
      1 => [1, 2],
      2 => [1],
      3 => [2],
    ];
    for ($i = 1; $i <= 3; $i++) {
      $field_names[$i] = 'field_' . $i;
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_names[$i],
        'entity_type' => $entity_type,
        'type' => 'test_field',
      ]);
      $field_storage->save();
      foreach ($field_bundles_map[$i] as $bundle) {
        FieldConfig::create([
          'field_name' => $field_names[$i],
          'entity_type' => $entity_type,
          'bundle' => $bundles[$bundle],
        ])->save();
      }
    }

    // Create one test entity per bundle, with random values.
    foreach ($bundles as $index => $bundle) {
      $entities[$index] = $this->container->get('entity_type.manager')
        ->getStorage($entity_type)
        ->create(['id' => $index, 'revision_id' => $index, 'type' => $bundle]);
      $entity = clone($entities[$index]);
      foreach ($field_names as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        $values[$index][$field_name] = mt_rand(1, 127);
        $entity->$field_name->setValue(['value' => $values[$index][$field_name]]);
      }
      $entity->enforceIsNew();
      $entity->save();
    }

    // Check that a single load correctly loads field values for both entities.
    $controller = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $entities = $controller->loadMultiple();
    foreach ($entities as $index => $entity) {
      foreach ($field_names as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        // The field value loaded matches the one inserted.
        $this->assertEquals($values[$index][$field_name], $entity->{$field_name}->value, "Entity $index: expected value was found.");
      }
    }
  }

  /**
   * Tests insert and update with empty or NULL fields.
   */
  public function testFieldAttachSaveEmptyData(): void {
    $entity_type = 'entity_test';
    $this->createFieldWithStorage('', $entity_type);

    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['id' => 1]);

    // Insert: Field is NULL.
    $entity = clone $entity_init;
    $entity->{$this->fieldTestData->field_name} = NULL;
    $entity->enforceIsNew();
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->fieldTestData->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // All saves after this point should be updates, not inserts.
    $entity_init->enforceIsNew(FALSE);

    // Add some real data.
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->fieldTestData->field_name} = $values;
    $entity = $this->entitySaveReload($entity);
    $this->assertEquals($values, $entity->{$this->fieldTestData->field_name}->getValue(), 'Field data saved');

    // Update: Field is NULL. Data should be wiped.
    $entity = clone($entity_init);
    $entity->{$this->fieldTestData->field_name} = NULL;
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->fieldTestData->field_name}->isEmpty(), 'Update: NULL field removes existing values');

    // Re-add some data.
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues(1);
    $entity->{$this->fieldTestData->field_name} = $values;
    $entity = $this->entitySaveReload($entity);
    $this->assertEquals($values, $entity->{$this->fieldTestData->field_name}->getValue(), 'Field data saved');

    // Update: Field is empty array. Data should be wiped.
    $entity = clone($entity_init);
    $entity->{$this->fieldTestData->field_name} = [];
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->fieldTestData->field_name}->isEmpty(), 'Update: empty array removes existing values');
  }

  /**
   * Tests insert with empty or NULL fields, with default value.
   */
  public function testFieldAttachSaveEmptyDataDefaultValue(): void {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('', $entity_type);

    // Add a default value function.
    $this->fieldTestData->field->set('default_value_callback', FieldTestHelper::class . '::defaultValue');
    $this->fieldTestData->field->save();

    // Verify that fields are populated with default values.
    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['id' => 1, 'revision_id' => 1]);
    $default = FieldTestHelper::defaultValue($entity_init, $this->fieldTestData->field);
    $this->assertEquals($default, $entity_init->{$this->fieldTestData->field_name}->getValue(), 'Default field value correctly populated.');

    // Insert: Field is NULL.
    $entity = clone($entity_init);
    $entity->{$this->fieldTestData->field_name} = NULL;
    $entity->enforceIsNew();
    $entity = $this->entitySaveReload($entity);
    $this->assertTrue($entity->{$this->fieldTestData->field_name}->isEmpty(), 'Insert: NULL field results in no value saved');

    // Verify that prepopulated field values are not overwritten by defaults.
    $value = [['value' => $default[0]['value'] - mt_rand(1, 127)]];
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['type' => $entity_init->bundle(), $this->fieldTestData->field_name => $value]);
    $this->assertEquals($value, $entity->{$this->fieldTestData->field_name}->getValue(), 'Prepopulated field value correctly maintained.');
  }

  /**
   * Tests entity deletion.
   */
  public function testFieldAttachDelete(): void {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('', $entity_type);
    $cardinality = $this->fieldTestData->field_storage->getCardinality();
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['type' => $this->fieldTestData->field->getTargetBundle()]);
    $vids = [];

    // Create revision 0
    $values = $this->_generateTestFieldValues($cardinality);
    $entity->{$this->fieldTestData->field_name} = $values;
    $entity->save();
    $vids[] = $entity->getRevisionId();

    // Create revision 1
    $entity->setNewRevision();
    $entity->save();
    $vids[] = $entity->getRevisionId();

    // Create revision 2
    $entity->setNewRevision();
    $entity->save();
    $vids[] = $entity->getRevisionId();
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $controller */
    $controller = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();

    // Confirm each revision loads
    foreach ($vids as $vid) {
      $revision = $controller->loadRevision($vid);
      $this->assertCount($cardinality, $revision->{$this->fieldTestData->field_name}, "The test entity revision $vid has $cardinality values.");
    }

    // Delete revision 1, confirm the other two still load.
    $controller->deleteRevision($vids[1]);
    $controller->resetCache();
    foreach ([0, 2] as $key) {
      $vid = $vids[$key];
      $revision = $controller->loadRevision($vid);
      $this->assertCount($cardinality, $revision->{$this->fieldTestData->field_name}, "The test entity revision $vid has $cardinality values.");
    }

    // Confirm the current revision still loads
    $controller->resetCache();
    $current = $controller->load($entity->id());
    $this->assertCount($cardinality, $current->{$this->fieldTestData->field_name}, "The test entity current revision has $cardinality values.");

    // Delete all field data, confirm nothing loads
    $entity->delete();
    $controller->resetCache();
    foreach ([0, 1, 2] as $vid) {
      $revision = $controller->loadRevision($vid);
      $this->assertNull($revision);
    }
    $this->assertNull($controller->load($entity->id()));
  }

  /**
   * Tests entity_bundle_create().
   */
  public function testEntityCreateBundle(): void {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('', $entity_type);
    $cardinality = $this->fieldTestData->field_storage->getCardinality();

    // Create a new bundle.
    $new_bundle = 'test_bundle_' . $this->randomMachineName();
    EntityTestHelper::createBundle($new_bundle, NULL, $entity_type);

    // Add a field to that bundle.
    $this->fieldTestData->field_definition['bundle'] = $new_bundle;
    FieldConfig::create($this->fieldTestData->field_definition)->save();

    // Save an entity with data in the field.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['type' => $this->fieldTestData->field->getTargetBundle()]);
    $values = $this->_generateTestFieldValues($cardinality);
    $entity->{$this->fieldTestData->field_name} = $values;

    // Verify the field data is present on load.
    $entity = $this->entitySaveReload($entity);
    $this->assertCount($cardinality, $entity->{$this->fieldTestData->field_name}, "Data is retrieved for the new bundle");
  }

  /**
   * Tests entity_bundle_delete().
   */
  public function testEntityDeleteBundle(): void {
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('', $entity_type);

    // Create a new bundle.
    $new_bundle = 'test_bundle_' . $this->randomMachineName();
    EntityTestHelper::createBundle($new_bundle, NULL, $entity_type);

    // Add a field to that bundle.
    $this->fieldTestData->field_definition['bundle'] = $new_bundle;
    FieldConfig::create($this->fieldTestData->field_definition)->save();

    // Create a second field for the test bundle
    $field_name = $this->randomMachineName() . '_field_name';
    $field_storage = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'test_field',
      'cardinality' => 1,
    ];
    FieldStorageConfig::create($field_storage)->save();
    $field = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $this->fieldTestData->field->getTargetBundle(),
      'label' => $this->randomMachineName() . '_label',
      'description' => $this->randomMachineName() . '_description',
      'weight' => mt_rand(0, 127),
    ];
    FieldConfig::create($field)->save();

    // Save an entity with data for both fields
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['type' => $this->fieldTestData->field->getTargetBundle()]);
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage->getCardinality());
    $entity->{$this->fieldTestData->field_name} = $values;
    $entity->{$field_name} = $this->_generateTestFieldValues(1);
    $entity = $this->entitySaveReload($entity);

    // Verify the fields are present on load
    $this->assertCount(4, $entity->{$this->fieldTestData->field_name}, 'First field got loaded');
    $this->assertCount(1, $entity->{$field_name}, 'Second field got loaded');

    // Delete the bundle. The form display has to be deleted first to prevent
    // schema errors when fields attached to the deleted bundle are themselves
    // deleted, which triggers an update of the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay($entity_type, $this->fieldTestData->field->getTargetBundle())
      ->delete();
    EntityTestHelper::deleteBundle($this->fieldTestData->field->getTargetBundle(), $entity_type);

    // Verify no data gets loaded
    $controller = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $entity = $controller->load($entity->id());

    $this->assertEmpty($entity->{$this->fieldTestData->field_name}, 'No data for first field');
    $this->assertEmpty($entity->{$field_name}, 'No data for second field');

    // Verify that the fields are gone.
    $this->assertNull(FieldConfig::load('entity_test.' . $this->fieldTestData->field->getTargetBundle() . '.' . $this->fieldTestData->field_name), "First field is deleted");
    $this->assertNull(FieldConfig::load('entity_test.' . $field['bundle'] . '.' . $field_name), "Second field is deleted");
  }

}
