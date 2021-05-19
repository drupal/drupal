<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests other Field API functions.
 *
 * @group field
 * @todo move this to the Entity module
 */
class FieldAttachOtherTest extends FieldKernelTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
    $this->createFieldWithStorage();
  }

  /**
   * Test rendering fields with EntityDisplay build().
   */
  public function testEntityDisplayBuild() {
    $this->createFieldWithStorage('_2');

    $entity_type = 'entity_test';
    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();

    // Populate values to be displayed.
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage->getCardinality());
    $entity_init->{$this->fieldTestData->field_name}->setValue($values);
    $values_2 = $this->_generateTestFieldValues($this->fieldTestData->field_storage_2->getCardinality());
    $entity_init->{$this->fieldTestData->field_name_2}->setValue($values_2);

    // Simple formatter, label displayed.
    $entity = clone($entity_init);
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($entity_type, $entity->bundle(), 'full');

    $formatter_setting = $this->randomMachineName();
    $display_options = [
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => [
        'test_formatter_setting' => $formatter_setting,
      ],
    ];
    $display->setComponent($this->fieldTestData->field_name, $display_options);

    $formatter_setting_2 = $this->randomMachineName();
    $display_options_2 = [
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => [
        'test_formatter_setting' => $formatter_setting_2,
      ],
    ];
    $display->setComponent($this->fieldTestData->field_name_2, $display_options_2);

    // View all fields.
    $content = $display->build($entity);
    $this->render($content);
    $this->assertRaw($this->fieldTestData->field->getLabel(), "First field's label is displayed.");
    foreach ($values as $delta => $value) {
      $this->assertRaw("$formatter_setting|{$value['value']}", "Value $delta is displayed, formatter settings are applied.");
    }
    $this->assertRaw($this->fieldTestData->field_2->getLabel(), "Second field's label is displayed.");
    foreach ($values_2 as $delta => $value) {
      $this->assertRaw("$formatter_setting_2|{$value['value']}", "Value $delta is displayed, formatter settings are applied.");
    }

    // Label hidden.
    $entity = clone($entity_init);
    $display_options['label'] = 'hidden';
    $display->setComponent($this->fieldTestData->field_name, $display_options);
    $content = $display->build($entity);
    $this->render($content);
    $this->assertNoRaw($this->fieldTestData->field->getLabel(), "Hidden label: label is not displayed.");

    // Field hidden.
    $entity = clone($entity_init);
    $display->removeComponent($this->fieldTestData->field_name);
    $content = $display->build($entity);
    $this->render($content);
    $this->assertNoRaw($this->fieldTestData->field->getLabel(), "Hidden field: label is not displayed.");
    foreach ($values as $delta => $value) {
      $this->assertNoRaw("$formatter_setting|{$value['value']}", "Hidden field: value $delta is not displayed.");
    }

    // Multiple formatter.
    $entity = clone($entity_init);
    $formatter_setting = $this->randomMachineName();
    $display->setComponent($this->fieldTestData->field_name, [
      'label' => 'above',
      'type' => 'field_test_multiple',
      'settings' => [
        'test_formatter_setting_multiple' => $formatter_setting,
      ],
    ]);
    $content = $display->build($entity);
    $this->render($content);
    $expected_output = $formatter_setting;
    foreach ($values as $delta => $value) {
      $expected_output .= "|$delta:{$value['value']}";
    }
    $this->assertRaw($expected_output, "Multiple formatter: all values are displayed, formatter settings are applied.");

    // Test a formatter that uses hook_field_formatter_prepare_view().
    $entity = clone($entity_init);
    $formatter_setting = $this->randomMachineName();
    $display->setComponent($this->fieldTestData->field_name, [
      'label' => 'above',
      'type' => 'field_test_with_prepare_view',
      'settings' => [
        'test_formatter_setting_additional' => $formatter_setting,
      ],
    ]);
    $content = $display->build($entity);
    $this->render($content);
    foreach ($values as $delta => $value) {
      $expected = $formatter_setting . '|' . $value['value'] . '|' . ($value['value'] + 1);
      $this->assertRaw($expected, "Value $delta is displayed, formatter settings are applied.");
    }

    // TODO:
    // - check display order with several fields
  }

  /**
   * Tests rendering fields with EntityDisplay::buildMultiple().
   */
  public function testEntityDisplayViewMultiple() {
    // Use a formatter that has a prepareView() step.
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($this->fieldTestData->field_name, [
        'type' => 'field_test_with_prepare_view',
      ]);

    // Create two entities.
    $entity1 = EntityTest::create(['id' => 1, 'type' => 'entity_test']);
    $entity1->{$this->fieldTestData->field_name}->setValue($this->_generateTestFieldValues(1));
    $entity2 = EntityTest::create(['id' => 2, 'type' => 'entity_test']);
    $entity2->{$this->fieldTestData->field_name}->setValue($this->_generateTestFieldValues(1));

    // Run buildMultiple(), and check that the entities come out as expected.
    $display->buildMultiple([$entity1, $entity2]);
    $item1 = $entity1->{$this->fieldTestData->field_name}[0];
    $this->assertEqual($item1->value + 1, $item1->additional_formatter_value, 'Entity 1 ran through the prepareView() formatter method.');
    $item2 = $entity2->{$this->fieldTestData->field_name}[0];
    $this->assertEqual($item2->value + 1, $item2->additional_formatter_value, 'Entity 2 ran through the prepareView() formatter method.');
  }

  /**
   * Test entity cache.
   *
   * Complements unit test coverage in
   * \Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTest.
   */
  public function testEntityCache() {
    // Initialize random values and a test entity.
    $entity_init = EntityTest::create(['type' => $this->fieldTestData->field->getTargetBundle()]);
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage->getCardinality());

    // Non-cacheable entity type.
    $entity_type = 'entity_test';
    $cid = "values:$entity_type:" . $entity_init->id();

    // Check that no initial cache entry is present.
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Non-cached: no initial cache entry');

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->fieldTestData->field_name}->setValue($values);
    $entity = $this->entitySaveReload($entity);
    $cid = "values:$entity_type:" . $entity->id();
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Non-cached: no cache entry on insert and load');

    // Cacheable entity type.
    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('_2', $entity_type);

    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'type' => $entity_type,
      ]);

    // Check that no initial cache entry is present.
    $cid = "values:$entity_type:" . $entity->id();
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Cached: no initial cache entry');

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->fieldTestData->field_name_2} = $values;
    $entity->save();
    $cid = "values:$entity_type:" . $entity->id();

    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Cached: no cache entry on insert');
    // Load, and check that a cache entry is present with the expected values.
    $controller = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache();
    $cached_entity = $controller->load($entity->id());
    $cache = \Drupal::cache('entity')->get($cid);
    $this->assertEqual($cached_entity, $cache->data, 'Cached: correct cache entry on load');

    // Update with different values, and check that the cache entry is wiped.
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage_2->getCardinality());
    $entity->{$this->fieldTestData->field_name_2} = $values;
    $entity->save();
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Cached: no cache entry on update');

    // Load, and check that a cache entry is present with the expected values.
    $controller->resetCache();
    $cached_entity = $controller->load($entity->id());
    $cache = \Drupal::cache('entity')->get($cid);
    $this->assertEqual($cached_entity, $cache->data, 'Cached: correct cache entry on load');

    // Create a new revision, and check that the cache entry is wiped.
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage_2->getCardinality());
    $entity->{$this->fieldTestData->field_name_2} = $values;
    $entity->setNewRevision();
    $entity->save();
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Cached: no cache entry on new revision creation');

    // Load, and check that a cache entry is present with the expected values.
    $controller->resetCache();
    $cached_entity = $controller->load($entity->id());
    $cache = \Drupal::cache('entity')->get($cid);
    $this->assertEqual($cached_entity, $cache->data, 'Cached: correct cache entry on load');

    // Delete, and check that the cache entry is wiped.
    $entity->delete();
    $this->assertFalse(\Drupal::cache('entity')->get($cid), 'Cached: no cache entry after delete');
  }

  /**
   * Tests \Drupal\Core\Entity\Display\EntityFormDisplayInterface::buildForm().
   *
   * This could be much more thorough, but it does verify that the correct
   * widgets show up.
   */
  public function testEntityFormDisplayBuildForm() {
    $this->createFieldWithStorage('_2');

    $entity_type = 'entity_test';
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create([
      'id' => 1,
      'revision_id' => 1,
      'type' => $this->fieldTestData->field->getTargetBundle(),
    ]);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Test generating widgets for all fields.
    $display = $display_repository->getFormDisplay($entity_type, $this->fieldTestData->field->getTargetBundle());
    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $this->assertEqual($this->fieldTestData->field->getLabel(), $form[$this->fieldTestData->field_name]['widget']['#title'], "First field's form title is {$this->fieldTestData->field->getLabel()}");
    $this->assertEqual($this->fieldTestData->field_2->getLabel(), $form[$this->fieldTestData->field_name_2]['widget']['#title'], "Second field's form title is {$this->fieldTestData->field_2->getLabel()}");
    for ($delta = 0; $delta < $this->fieldTestData->field_storage->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual('textfield', $form[$this->fieldTestData->field_name]['widget'][$delta]['value']['#type'], "First field's form delta {$delta} widget is textfield");
    }
    for ($delta = 0; $delta < $this->fieldTestData->field_storage_2->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual('textfield', $form[$this->fieldTestData->field_name_2]['widget'][$delta]['value']['#type'], "Second field's form delta {$delta} widget is textfield");
    }

    // Test generating widgets for all fields.
    $display = $display_repository->getFormDisplay($entity_type, $this->fieldTestData->field->getTargetBundle());
    foreach ($display->getComponents() as $name => $options) {
      if ($name != $this->fieldTestData->field_name_2) {
        $display->removeComponent($name);
      }
    }
    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $this->assertFalse(isset($form[$this->fieldTestData->field_name]), 'The first field does not exist in the form');
    $this->assertEqual($this->fieldTestData->field_2->getLabel(), $form[$this->fieldTestData->field_name_2]['widget']['#title'], "Second field's form title is {$this->fieldTestData->field_2->getLabel()}");
    for ($delta = 0; $delta < $this->fieldTestData->field_storage_2->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual('textfield', $form[$this->fieldTestData->field_name_2]['widget'][$delta]['value']['#type'], "Second field's form delta {$delta} widget is textfield");
    }
  }

  /**
   * Tests \Drupal\Core\Entity\Display\EntityFormDisplayInterface::extractFormValues().
   */
  public function testEntityFormDisplayExtractFormValues() {
    $this->createFieldWithStorage('_2');

    $entity_type = 'entity_test';
    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['id' => 1, 'revision_id' => 1, 'type' => $this->fieldTestData->field->getTargetBundle()]);

    // Build the form for all fields.
    $display = \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $this->fieldTestData->field->getTargetBundle());
    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity_init, $form, $form_state);

    // Simulate incoming values.
    // First field.
    $values = [];
    $weights = [];
    for ($delta = 0; $delta < $this->fieldTestData->field_storage->getCardinality(); $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
      // Assign random weight.
      do {
        $weight = mt_rand(0, $this->fieldTestData->field_storage->getCardinality());
      } while (in_array($weight, $weights));
      $weights[$delta] = $weight;
      $values[$delta]['_weight'] = $weight;
    }
    // Leave an empty value. 'field_test' fields are empty if empty().
    $values[1]['value'] = 0;
    // Second field.
    $values_2 = [];
    $weights_2 = [];
    for ($delta = 0; $delta < $this->fieldTestData->field_storage_2->getCardinality(); $delta++) {
      $values_2[$delta]['value'] = mt_rand(1, 127);
      // Assign random weight.
      do {
        $weight = mt_rand(0, $this->fieldTestData->field_storage_2->getCardinality());
      } while (in_array($weight, $weights_2));
      $weights_2[$delta] = $weight;
      $values_2[$delta]['_weight'] = $weight;
    }
    // Leave an empty value. 'field_test' fields are empty if empty().
    $values_2[1]['value'] = 0;

    // Pretend the form has been built.
    $form_state->setFormObject(\Drupal::entityTypeManager()->getFormObject($entity_type, 'default'));
    \Drupal::formBuilder()->prepareForm('field_test_entity_form', $form, $form_state);
    \Drupal::formBuilder()->processForm('field_test_entity_form', $form, $form_state);
    $form_state->setValue($this->fieldTestData->field_name, $values);
    $form_state->setValue($this->fieldTestData->field_name_2, $values_2);

    // Extract values for all fields.
    $entity = clone($entity_init);
    $display->extractFormValues($entity, $form, $form_state);

    asort($weights);
    asort($weights_2);
    $expected_values = [];
    $expected_values_2 = [];
    foreach ($weights as $key => $value) {
      if ($key != 1) {
        $expected_values[] = ['value' => $values[$key]['value']];
      }
    }
    $this->assertSame($expected_values, $entity->{$this->fieldTestData->field_name}->getValue(), 'Submit filters empty values');
    foreach ($weights_2 as $key => $value) {
      if ($key != 1) {
        $expected_values_2[] = ['value' => $values_2[$key]['value']];
      }
    }
    $this->assertSame($expected_values_2, $entity->{$this->fieldTestData->field_name_2}->getValue(), 'Submit filters empty values');

    // Call EntityFormDisplayInterface::extractFormValues() for a single field (the second field).
    foreach ($display->getComponents() as $name => $options) {
      if ($name != $this->fieldTestData->field_name_2) {
        $display->removeComponent($name);
      }
    }
    $entity = clone($entity_init);
    $display->extractFormValues($entity, $form, $form_state);
    $expected_values_2 = [];
    foreach ($weights_2 as $key => $value) {
      if ($key != 1) {
        $expected_values_2[] = ['value' => $values_2[$key]['value']];
      }
    }
    $this->assertTrue($entity->{$this->fieldTestData->field_name}->isEmpty(), 'The first field is empty in the entity object');
    $this->assertSame($expected_values_2, $entity->{$this->fieldTestData->field_name_2}->getValue(), 'Submit filters empty values');
  }

}
