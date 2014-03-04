<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAttachOtherTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

/**
 * Unit test class for non-storage related field_attach_* functions.
 */
class FieldAttachOtherTest extends FieldUnitTestBase {

  /**
   * Field name to use in the test.
   *
   * @var string
   */
  protected $field_name;

  /**
   * Field name to use in the test.
   *
   * @var string
   */
  protected $field_name_2;

  public static function getInfo() {
    return array(
      'name' => 'Field attach tests (other)',
      'description' => 'Test other Field Attach API functions.',
      'group' => 'Field API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->createFieldWithInstance();
  }

  /**
   * Test rendering fields with EntityDisplay build().
   */
  function testEntityDisplayBuild() {
    $this->createFieldWithInstance('_2');

    $entity_type = 'entity_test';
    $entity_init = entity_create($entity_type);

    // Populate values to be displayed.
    $values = $this->_generateTestFieldValues($this->field->getCardinality());
    $entity_init->{$this->field_name}->setValue($values);
    $values_2 = $this->_generateTestFieldValues($this->field_2->getCardinality());
    $entity_init->{$this->field_name_2}->setValue($values_2);

    // Simple formatter, label displayed.
    $entity = clone($entity_init);
    $display = entity_get_display($entity_type, $entity->bundle(), 'full');

    $formatter_setting = $this->randomName();
    $display_options = array(
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => array(
        'test_formatter_setting' => $formatter_setting,
      ),
    );
    $display->setComponent($this->field->getName(), $display_options);

    $formatter_setting_2 = $this->randomName();
    $display_options_2 = array(
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => array(
        'test_formatter_setting' => $formatter_setting_2,
      ),
    );
    $display->setComponent($this->field_2->getName(), $display_options_2);

    // View all fields.
    $content = $display->build($entity);
    $this->content = drupal_render($content);
    $this->assertRaw($this->instance->getLabel(), "First field's label is displayed.");
    foreach ($values as $delta => $value) {
      $this->assertRaw("$formatter_setting|{$value['value']}", "Value $delta is displayed, formatter settings are applied.");
    }
    $this->assertRaw($this->instance_2->getLabel(), "Second field's label is displayed.");
    foreach ($values_2 as $delta => $value) {
      $this->assertRaw("$formatter_setting_2|{$value['value']}", "Value $delta is displayed, formatter settings are applied.");
    }

    // Label hidden.
    $entity = clone($entity_init);
    $display_options['label'] = 'hidden';
    $display->setComponent($this->field->getName(), $display_options);
    $content = $display->build($entity);
    $this->content = drupal_render($content);
    $this->assertNoRaw($this->instance->getLabel(), "Hidden label: label is not displayed.");

    // Field hidden.
    $entity = clone($entity_init);
    $display->removeComponent($this->field->getName());
    $content = $display->build($entity);
    $this->content = drupal_render($content);
    $this->assertNoRaw($this->instance->getLabel(), "Hidden field: label is not displayed.");
    foreach ($values as $delta => $value) {
      $this->assertNoRaw("$formatter_setting|{$value['value']}", "Hidden field: value $delta is not displayed.");
    }

    // Multiple formatter.
    $entity = clone($entity_init);
    $formatter_setting = $this->randomName();
    $display->setComponent($this->field->getName(), array(
      'label' => 'above',
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $formatter_setting,
      ),
    ));
    $content = $display->build($entity);
    $this->content = drupal_render($content);
    $expected_output = $formatter_setting;
    foreach ($values as $delta => $value) {
      $expected_output .= "|$delta:{$value['value']}";
    }
    $this->assertRaw($expected_output, "Multiple formatter: all values are displayed, formatter settings are applied.");

    // Test a formatter that uses hook_field_formatter_prepare_view().
    $entity = clone($entity_init);
    $formatter_setting = $this->randomName();
    $display->setComponent($this->field->getName(), array(
      'label' => 'above',
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $formatter_setting,
      ),
    ));
    $content = $display->build($entity);
    $this->content = drupal_render($content);
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
  function testEntityDisplayViewMultiple() {
    // Use a formatter that has a prepareView() step.
    $display = entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($this->field_name, array(
        'type' => 'field_test_with_prepare_view',
      ));

    // Create two entities.
    $entity1 = entity_create('entity_test', array('id' => 1, 'type' => 'entity_test'));
    $entity1->{$this->field_name}->setValue($this->_generateTestFieldValues(1));
    $entity2 = entity_create('entity_test', array('id' => 2, 'type' => 'entity_test'));
    $entity2->{$this->field_name}->setValue($this->_generateTestFieldValues(1));

    // Run buildMultiple(), and check that the entities come out as expected.
    $display->buildMultiple(array($entity1, $entity2));
    $item1 = $entity1->{$this->field_name}[0];
    $this->assertEqual($item1->additional_formatter_value, $item1->value + 1, 'Entity 1 ran through the prepareView() formatter method.');
    $item2 = $entity2->{$this->field_name}[0];
    $this->assertEqual($item2->additional_formatter_value, $item2->value + 1, 'Entity 2 ran through the prepareView() formatter method.');
  }

  /**
   * Test field cache.
   */
  function testFieldAttachCache() {
    // Initialize random values and a test entity.
    $entity_init = entity_create('entity_test', array('type' => $this->instance->bundle));
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $values = $this->_generateTestFieldValues($this->field->getCardinality());

    // Non-cacheable entity type.
    $entity_type = 'entity_test';
    $cid = "field:$entity_type:" . $entity_init->id();

    // Check that no initial cache entry is present.
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Non-cached: no initial cache entry');

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->field_name}->setValue($values);
    $entity = $this->entitySaveReload($entity);
    $cid = "field:$entity_type:" . $entity->id();
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Non-cached: no cache entry on insert and load');

    // Cacheable entity type.
    $entity_type = 'entity_test_cache';
    $this->createFieldWithInstance('_2', 'entity_test_cache');
    entity_info_cache_clear();

    $entity_init = entity_create($entity_type, array(
      'type' => $entity_type,
    ));

    // Check that no initial cache entry is present.
    $cid = "field:$entity_type:" . $entity->id();
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Cached: no initial cache entry');

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->field_name_2} = $values;
    $entity->save();
    $cid = "field:$entity_type:" . $entity->id();

    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Cached: no cache entry on insert');
    // Load, and check that a cache entry is present with the expected values.
    $controller = $this->container->get('entity.manager')->getStorageController($entity->getEntityTypeId());
    $controller->resetCache();
    $controller->load($entity->id());
    $cache = \Drupal::cache('field')->get($cid);
    $this->assertEqual($cache->data[$langcode][$this->field_name_2], $values, 'Cached: correct cache entry on load');

    // Update with different values, and check that the cache entry is wiped.
    $values = $this->_generateTestFieldValues($this->field_2->getCardinality());
    $entity = entity_create($entity_type, array(
      'type' => $entity_type,
      'id' => $entity->id(),
    ));
    $entity->{$this->field_name_2} = $values;
    $entity->save();
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Cached: no cache entry on update');

    // Load, and check that a cache entry is present with the expected values.
    $controller->resetCache();
    $controller->load($entity->id());
    $cache = \Drupal::cache('field')->get($cid);
    $this->assertEqual($cache->data[$langcode][$this->field_name_2], $values, 'Cached: correct cache entry on load');

    // Create a new revision, and check that the cache entry is wiped.
    $entity = entity_create($entity_type, array(
      'type' => $entity_type,
      'id' => $entity->id(),
    ));
    $values = $this->_generateTestFieldValues($this->field_2->getCardinality());
    $entity->{$this->field_name_2} = $values;
    $entity->setNewRevision();
    $entity->save();
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Cached: no cache entry on new revision creation');

    // Load, and check that a cache entry is present with the expected values.
    $controller->resetCache();
    $controller->load($entity->id());
    $cache = \Drupal::cache('field')->get($cid);
    $this->assertEqual($cache->data[$langcode][$this->field_name_2], $values, 'Cached: correct cache entry on load');

    // Delete, and check that the cache entry is wiped.
    $entity->delete();
    $this->assertFalse(\Drupal::cache('field')->get($cid), 'Cached: no cache entry after delete');
  }

  /**
   * Test field_attach_form().
   *
   * This could be much more thorough, but it does verify that the correct
   * widgets show up.
   */
  function testFieldAttachForm() {
    $this->createFieldWithInstance('_2');

    $entity_type = 'entity_test';
    $entity = entity_create($entity_type, array('id' => 1, 'revision_id' => 1, 'type' => $this->instance->bundle));

    // When generating form for all fields.
    $form = array();
    $form_state = form_state_defaults();
    $form_state['form_display'] = entity_get_form_display($entity_type, $this->instance->bundle, 'default');
    field_attach_form($entity, $form, $form_state);

    $this->assertEqual($form[$this->field_name]['widget']['#title'], $this->instance->getLabel(), "First field's form title is {$this->instance->getLabel()}");
    $this->assertEqual($form[$this->field_name_2]['widget']['#title'], $this->instance_2->getLabel(), "Second field's form title is {$this->instance_2->getLabel()}");
    for ($delta = 0; $delta < $this->field->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual($form[$this->field_name]['widget'][$delta]['value']['#type'], 'textfield', "First field's form delta $delta widget is textfield");
    }
    for ($delta = 0; $delta < $this->field_2->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual($form[$this->field_name_2]['widget'][$delta]['value']['#type'], 'textfield', "Second field's form delta $delta widget is textfield");
    }

    // When generating form for a single field (the second field).
    $options = array('field_name' => $this->field_name_2);
    $form = array();
    $form_state = form_state_defaults();
    $form_state['form_display'] = entity_get_form_display($entity_type, $this->instance->bundle, 'default');
    field_attach_form($entity, $form, $form_state, NULL, $options);

    $this->assertFalse(isset($form[$this->field_name]), 'The first field does not exist in the form');
    $this->assertEqual($form[$this->field_name_2]['widget']['#title'], $this->instance_2->getLabel(), "Second field's form title is {$this->instance_2->getLabel()}");
    for ($delta = 0; $delta < $this->field_2->getCardinality(); $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual($form[$this->field_name_2]['widget'][$delta]['value']['#type'], 'textfield', "Second field's form delta $delta widget is textfield");
    }
  }

  /**
   * Test field_attach_extract_form_values().
   */
  function testFieldAttachExtractFormValues() {
    $this->createFieldWithInstance('_2');

    $entity_type = 'entity_test';
    $entity_init = entity_create($entity_type, array('id' => 1, 'revision_id' => 1, 'type' => $this->instance->bundle));

    // Build the form for all fields.
    $form = array();
    $form_state = form_state_defaults();
    $form_state['form_display'] = entity_get_form_display($entity_type, $this->instance->bundle, 'default');
    field_attach_form($entity_init, $form, $form_state);

    // Simulate incoming values.
    // First field.
    $values = array();
    $weights = array();
    for ($delta = 0; $delta < $this->field->getCardinality(); $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
      // Assign random weight.
      do {
        $weight = mt_rand(0, $this->field->getCardinality());
      } while (in_array($weight, $weights));
      $weights[$delta] = $weight;
      $values[$delta]['_weight'] = $weight;
    }
    // Leave an empty value. 'field_test' fields are empty if empty().
    $values[1]['value'] = 0;
    // Second field.
    $values_2 = array();
    $weights_2 = array();
    for ($delta = 0; $delta < $this->field_2->getCardinality(); $delta++) {
      $values_2[$delta]['value'] = mt_rand(1, 127);
      // Assign random weight.
      do {
        $weight = mt_rand(0, $this->field_2->getCardinality());
      } while (in_array($weight, $weights_2));
      $weights_2[$delta] = $weight;
      $values_2[$delta]['_weight'] = $weight;
    }
    // Leave an empty value. 'field_test' fields are empty if empty().
    $values_2[1]['value'] = 0;

    // Pretend the form has been built.
    drupal_prepare_form('field_test_entity_form', $form, $form_state);
    drupal_process_form('field_test_entity_form', $form, $form_state);
    $form_state['values'][$this->field_name] = $values;
    $form_state['values'][$this->field_name_2] = $values_2;

    // Call field_attach_extract_form_values() for all fields.
    $entity = clone($entity_init);
    field_attach_extract_form_values($entity, $form, $form_state);

    asort($weights);
    asort($weights_2);
    $expected_values = array();
    $expected_values_2 = array();
    foreach ($weights as $key => $value) {
      if ($key != 1) {
        $expected_values[] = array('value' => $values[$key]['value']);
      }
    }
    $this->assertIdentical($entity->{$this->field_name}->getValue(), $expected_values, 'Submit filters empty values');
    foreach ($weights_2 as $key => $value) {
      if ($key != 1) {
        $expected_values_2[] = array('value' => $values_2[$key]['value']);
      }
    }
    $this->assertIdentical($entity->{$this->field_name_2}->getValue(), $expected_values_2, 'Submit filters empty values');

    // Call field_attach_extract_form_values() for a single field (the second field).
    $options = array('field_name' => $this->field_name_2);
    $entity = clone($entity_init);
    field_attach_extract_form_values($entity, $form, $form_state, $options);
    $expected_values_2 = array();
    foreach ($weights_2 as $key => $value) {
      if ($key != 1) {
        $expected_values_2[] = array('value' => $values_2[$key]['value']);
      }
    }
    $this->assertTrue($entity->{$this->field_name}->isEmpty(), 'The first field does is empty in the entity object');
    $this->assertIdentical($entity->{$this->field_name_2}->getValue(), $expected_values_2, 'Submit filters empty values');
  }

}
