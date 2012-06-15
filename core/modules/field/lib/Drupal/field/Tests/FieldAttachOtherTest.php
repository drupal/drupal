<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAttachOtherTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\FieldValidationException;

/**
 * Unit test class for non-storage related field_attach_* functions.
 */
class FieldAttachOtherTest extends FieldAttachTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Field attach tests (other)',
      'description' => 'Test other Field Attach API functions.',
      'group' => 'Field API',
    );
  }

  /**
   * Test field_attach_view() and field_attach_prepare_view().
   */
  function testFieldAttachView() {
    $entity_type = 'test_entity';
    $entity_init = field_test_create_stub_entity();
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Populate values to be displayed.
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity_init->{$this->field_name}[$langcode] = $values;

    // Simple formatter, label displayed.
    $entity = clone($entity_init);
    $formatter_setting = $this->randomName();
    $this->instance['display'] = array(
      'full' => array(
        'label' => 'above',
        'type' => 'field_test_default',
        'settings' => array(
          'test_formatter_setting' => $formatter_setting,
        )
      ),
    );
    field_update_instance($this->instance);
    field_attach_prepare_view($entity_type, array($entity->ftid => $entity), 'full');
    $entity->content = field_attach_view($entity_type, $entity, 'full');
    $output = drupal_render($entity->content);
    $this->content = $output;
    $this->assertRaw($this->instance['label'], "Label is displayed.");
    foreach ($values as $delta => $value) {
      $this->content = $output;
      $this->assertRaw("$formatter_setting|{$value['value']}", "Value $delta is displayed, formatter settings are applied.");
    }

    // Label hidden.
    $entity = clone($entity_init);
    $this->instance['display']['full']['label'] = 'hidden';
    field_update_instance($this->instance);
    field_attach_prepare_view($entity_type, array($entity->ftid => $entity), 'full');
    $entity->content = field_attach_view($entity_type, $entity, 'full');
    $output = drupal_render($entity->content);
    $this->content = $output;
    $this->assertNoRaw($this->instance['label'], "Hidden label: label is not displayed.");

    // Field hidden.
    $entity = clone($entity_init);
    $this->instance['display'] = array(
      'full' => array(
        'label' => 'above',
        'type' => 'hidden',
      ),
    );
    field_update_instance($this->instance);
    field_attach_prepare_view($entity_type, array($entity->ftid => $entity), 'full');
    $entity->content = field_attach_view($entity_type, $entity, 'full');
    $output = drupal_render($entity->content);
    $this->content = $output;
    $this->assertNoRaw($this->instance['label'], "Hidden field: label is not displayed.");
    foreach ($values as $delta => $value) {
      $this->assertNoRaw($value['value'], "Hidden field: value $delta is not displayed.");
    }

    // Multiple formatter.
    $entity = clone($entity_init);
    $formatter_setting = $this->randomName();
    $this->instance['display'] = array(
      'full' => array(
        'label' => 'above',
        'type' => 'field_test_multiple',
        'settings' => array(
          'test_formatter_setting_multiple' => $formatter_setting,
        )
      ),
    );
    field_update_instance($this->instance);
    field_attach_prepare_view($entity_type, array($entity->ftid => $entity), 'full');
    $entity->content = field_attach_view($entity_type, $entity, 'full');
    $output = drupal_render($entity->content);
    $display = $formatter_setting;
    foreach ($values as $delta => $value) {
      $display .= "|$delta:{$value['value']}";
    }
    $this->content = $output;
    $this->assertRaw($display, "Multiple formatter: all values are displayed, formatter settings are applied.");

    // Test a formatter that uses hook_field_formatter_prepare_view().
    $entity = clone($entity_init);
    $formatter_setting = $this->randomName();
    $this->instance['display'] = array(
      'full' => array(
        'label' => 'above',
        'type' => 'field_test_with_prepare_view',
        'settings' => array(
          'test_formatter_setting_additional' => $formatter_setting,
        )
      ),
    );
    field_update_instance($this->instance);
    field_attach_prepare_view($entity_type, array($entity->ftid => $entity), 'full');
    $entity->content = field_attach_view($entity_type, $entity, 'full');
    $output = drupal_render($entity->content);
    $this->content = $output;
    foreach ($values as $delta => $value) {
      $this->content = $output;
      $expected = $formatter_setting . '|' . $value['value'] . '|' . ($value['value'] + 1);
      $this->assertRaw($expected, "Value $delta is displayed, formatter settings are applied.");
    }

    // TODO:
    // - check display order with several fields

    // Preprocess template.
    $variables = array();
    field_attach_preprocess($entity_type, $entity, $entity->content, $variables);
    $result = TRUE;
    foreach ($values as $delta => $item) {
      if ($variables[$this->field_name][$delta]['value'] !== $item['value']) {
        $result = FALSE;
        break;
      }
    }
    $this->assertTrue($result, t('Variable $@field_name correctly populated.', array('@field_name' => $this->field_name)));
  }

  /**
   * Tests the 'multiple entity' behavior of field_attach_prepare_view().
   */
  function testFieldAttachPrepareViewMultiple() {
    $entity_type = 'test_entity';
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Set the instance to be hidden.
    $this->instance['display']['full']['type'] = 'hidden';
    field_update_instance($this->instance);

    // Set up a second instance on another bundle, with a formatter that uses
    // hook_field_formatter_prepare_view().
    field_test_create_bundle('test_bundle_2');
    $formatter_setting = $this->randomName();
    $this->instance2 = $this->instance;
    $this->instance2['bundle'] = 'test_bundle_2';
    $this->instance2['display']['full'] = array(
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $formatter_setting,
      )
    );
    field_create_instance($this->instance2);

    // Create one entity in each bundle.
    $entity1_init = field_test_create_stub_entity(1, 1, 'test_bundle');
    $values1 = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity1_init->{$this->field_name}[$langcode] = $values1;

    $entity2_init = field_test_create_stub_entity(2, 2, 'test_bundle_2');
    $values2 = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity2_init->{$this->field_name}[$langcode] = $values2;

    // Run prepare_view, and check that the entities come out as expected.
    $entity1 = clone($entity1_init);
    $entity2 = clone($entity2_init);
    field_attach_prepare_view($entity_type, array($entity1->ftid => $entity1, $entity2->ftid => $entity2), 'full');
    $this->assertFalse(isset($entity1->{$this->field_name}[$langcode][0]['additional_formatter_value']), 'Entity 1 did not run through the prepare_view hook.');
    $this->assertTrue(isset($entity2->{$this->field_name}[$langcode][0]['additional_formatter_value']), 'Entity 2 ran through the prepare_view hook.');

    // Same thing, reversed order.
    $entity1 = clone($entity1_init);
    $entity2 = clone($entity2_init);
    field_attach_prepare_view($entity_type, array($entity2->ftid => $entity2, $entity1->ftid => $entity1), 'full');
    $this->assertFalse(isset($entity1->{$this->field_name}[$langcode][0]['additional_formatter_value']), 'Entity 1 did not run through the prepare_view hook.');
    $this->assertTrue(isset($entity2->{$this->field_name}[$langcode][0]['additional_formatter_value']), 'Entity 2 ran through the prepare_view hook.');
  }

  /**
   * Test field cache.
   */
  function testFieldAttachCache() {
    // Initialize random values and a test entity.
    $entity_init = field_test_create_stub_entity(1, 1, $this->instance['bundle']);
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $values = $this->_generateTestFieldValues($this->field['cardinality']);

    // Non-cacheable entity type.
    $entity_type = 'test_entity';
    $cid = "field:$entity_type:{$entity_init->ftid}";

    // Check that no initial cache entry is present.
    $this->assertFalse(cache('field')->get($cid), t('Non-cached: no initial cache entry'));

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->field_name}[$langcode] = $values;
    field_attach_insert($entity_type, $entity);
    $this->assertFalse(cache('field')->get($cid), t('Non-cached: no cache entry on insert'));

    // Load, and check that no cache entry is present.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->ftid => $entity));
    $this->assertFalse(cache('field')->get($cid), t('Non-cached: no cache entry on load'));


    // Cacheable entity type.
    $entity_type = 'test_cacheable_entity';
    $cid = "field:$entity_type:{$entity_init->ftid}";
    $instance = $this->instance;
    $instance['entity_type'] = $entity_type;
    field_create_instance($instance);

    // Check that no initial cache entry is present.
    $this->assertFalse(cache('field')->get($cid), t('Cached: no initial cache entry'));

    // Save, and check that no cache entry is present.
    $entity = clone($entity_init);
    $entity->{$this->field_name}[$langcode] = $values;
    field_attach_insert($entity_type, $entity);
    $this->assertFalse(cache('field')->get($cid), t('Cached: no cache entry on insert'));

    // Load a single field, and check that no cache entry is present.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->ftid => $entity), FIELD_LOAD_CURRENT, array('field_id' => $this->field_id));
    $cache = cache('field')->get($cid);
    $this->assertFalse(cache('field')->get($cid), t('Cached: no cache entry on loading a single field'));

    // Load, and check that a cache entry is present with the expected values.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->ftid => $entity));
    $cache = cache('field')->get($cid);
    $this->assertEqual($cache->data[$this->field_name][$langcode], $values, t('Cached: correct cache entry on load'));

    // Update with different values, and check that the cache entry is wiped.
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity = clone($entity_init);
    $entity->{$this->field_name}[$langcode] = $values;
    field_attach_update($entity_type, $entity);
    $this->assertFalse(cache('field')->get($cid), t('Cached: no cache entry on update'));

    // Load, and check that a cache entry is present with the expected values.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->ftid => $entity));
    $cache = cache('field')->get($cid);
    $this->assertEqual($cache->data[$this->field_name][$langcode], $values, t('Cached: correct cache entry on load'));

    // Create a new revision, and check that the cache entry is wiped.
    $entity_init = field_test_create_stub_entity(1, 2, $this->instance['bundle']);
    $values = $this->_generateTestFieldValues($this->field['cardinality']);
    $entity = clone($entity_init);
    $entity->{$this->field_name}[$langcode] = $values;
    field_attach_update($entity_type, $entity);
    $cache = cache('field')->get($cid);
    $this->assertFalse(cache('field')->get($cid), t('Cached: no cache entry on new revision creation'));

    // Load, and check that a cache entry is present with the expected values.
    $entity = clone($entity_init);
    field_attach_load($entity_type, array($entity->ftid => $entity));
    $cache = cache('field')->get($cid);
    $this->assertEqual($cache->data[$this->field_name][$langcode], $values, t('Cached: correct cache entry on load'));

    // Delete, and check that the cache entry is wiped.
    field_attach_delete($entity_type, $entity);
    $this->assertFalse(cache('field')->get($cid), t('Cached: no cache entry after delete'));
  }

  /**
   * Test field_attach_validate().
   *
   * Verify that field_attach_validate() invokes the correct
   * hook_field_validate.
   */
  function testFieldAttachValidate() {
    $entity_type = 'test_entity';
    $entity = field_test_create_stub_entity(0, 0, $this->instance['bundle']);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Set up values to generate errors
    $values = array();
    for ($delta = 0; $delta < $this->field['cardinality']; $delta++) {
      $values[$delta]['value'] = -1;
    }
    // Arrange for item 1 not to generate an error
    $values[1]['value'] = 1;
    $entity->{$this->field_name}[$langcode] = $values;

    try {
      field_attach_validate($entity_type, $entity);
    }
    catch (FieldValidationException $e) {
      $errors = $e->errors;
    }

    foreach ($values as $delta => $value) {
      if ($value['value'] != 1) {
        $this->assertIdentical($errors[$this->field_name][$langcode][$delta][0]['error'], 'field_test_invalid', "Error set on value $delta");
        $this->assertEqual(count($errors[$this->field_name][$langcode][$delta]), 1, "Only one error set on value $delta");
        unset($errors[$this->field_name][$langcode][$delta]);
      }
      else {
        $this->assertFalse(isset($errors[$this->field_name][$langcode][$delta]), "No error set on value $delta");
      }
    }
    $this->assertEqual(count($errors[$this->field_name][$langcode]), 0, 'No extraneous errors set');

    // Check that cardinality is validated.
    $entity->{$this->field_name}[$langcode] = $this->_generateTestFieldValues($this->field['cardinality'] + 1);
    try {
      field_attach_validate($entity_type, $entity);
    }
    catch (FieldValidationException $e) {
      $errors = $e->errors;
    }
    $this->assertEqual($errors[$this->field_name][$langcode][0][0]['error'], 'field_cardinality', t('Cardinality validation failed.'));

  }

  /**
   * Test field_attach_form().
   *
   * This could be much more thorough, but it does verify that the correct
   * widgets show up.
   */
  function testFieldAttachForm() {
    $entity_type = 'test_entity';
    $entity = field_test_create_stub_entity(0, 0, $this->instance['bundle']);

    $form = array();
    $form_state = form_state_defaults();
    field_attach_form($entity_type, $entity, $form, $form_state);

    $langcode = LANGUAGE_NOT_SPECIFIED;
    $this->assertEqual($form[$this->field_name][$langcode]['#title'], $this->instance['label'], "Form title is {$this->instance['label']}");
    for ($delta = 0; $delta < $this->field['cardinality']; $delta++) {
      // field_test_widget uses 'textfield'
      $this->assertEqual($form[$this->field_name][$langcode][$delta]['value']['#type'], 'textfield', "Form delta $delta widget is textfield");
    }
  }

  /**
   * Test field_attach_submit().
   */
  function testFieldAttachSubmit() {
    $entity_type = 'test_entity';
    $entity = field_test_create_stub_entity(0, 0, $this->instance['bundle']);

    // Build the form.
    $form = array();
    $form_state = form_state_defaults();
    field_attach_form($entity_type, $entity, $form, $form_state);

    // Simulate incoming values.
    $values = array();
    $weights = array();
    for ($delta = 0; $delta < $this->field['cardinality']; $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
      // Assign random weight.
      do {
        $weight = mt_rand(0, $this->field['cardinality']);
      } while (in_array($weight, $weights));
      $weights[$delta] = $weight;
      $values[$delta]['_weight'] = $weight;
    }
    // Leave an empty value. 'field_test' fields are empty if empty().
    $values[1]['value'] = 0;

    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Pretend the form has been built.
    drupal_prepare_form('field_test_entity_form', $form, $form_state);
    drupal_process_form('field_test_entity_form', $form, $form_state);
    $form_state['values'][$this->field_name][$langcode] = $values;
    field_attach_submit($entity_type, $entity, $form, $form_state);

    asort($weights);
    $expected_values = array();
    foreach ($weights as $key => $value) {
      if ($key != 1) {
        $expected_values[] = array('value' => $values[$key]['value']);
      }
    }
    $this->assertIdentical($entity->{$this->field_name}[$langcode], $expected_values, 'Submit filters empty values');
  }
}
