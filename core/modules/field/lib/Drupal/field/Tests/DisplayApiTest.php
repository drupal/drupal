<?php

/**
 * @file
 * Definition of Drupal\field\Tests\DisplayApiTest.
 */

namespace Drupal\field\Tests;

class DisplayApiTest extends FieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Field Display API tests',
      'description' => 'Test the display API.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp('field_test');

    // Create a field and instance.
    $this->field_name = 'test_field';
    $this->label = $this->randomName();
    $this->cardinality = 4;

    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'test_field',
      'cardinality' => $this->cardinality,
    );
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->label,
      'display' => array(
        'default' => array(
          'type' => 'field_test_default',
          'settings' => array(
            'test_formatter_setting' => $this->randomName(),
          ),
        ),
        'teaser' => array(
          'type' => 'field_test_default',
          'settings' => array(
            'test_formatter_setting' => $this->randomName(),
          ),
        ),
      ),
    );
    field_create_field($this->field);
    field_create_instance($this->instance);

    // Create an entity with values.
    $this->values = $this->_generateTestFieldValues($this->cardinality);
    $this->entity = field_test_create_stub_entity();
    $this->is_new = TRUE;
    $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED] = $this->values;
    field_test_entity_save($this->entity);
  }

  /**
   * Test the field_view_field() function.
   */
  function testFieldViewField() {
    // No display settings: check that default display settings are used.
    $output = field_view_field('test_entity', $this->entity, $this->field_name);
    $this->drupalSetContent(drupal_render($output));
    $settings = field_info_formatter_settings('field_test_default');
    $setting = $settings['test_formatter_setting'];
    $this->assertText($this->label, t('Label was displayed.'));
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that explicit display settings are used.
    $display = array(
      'label' => 'hidden',
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomName(),
        'alter' => TRUE,
      ),
    );
    $output = field_view_field('test_entity', $this->entity, $this->field_name, $display);
    $this->drupalSetContent(drupal_render($output));
    $setting = $display['settings']['test_formatter_setting_multiple'];
    $this->assertNoText($this->label, t('Label was not displayed.'));
    $this->assertText('field_test_field_attach_view_alter', t('Alter fired, display passed.'));
    $array = array();
    foreach ($this->values as $delta => $value) {
      $array[] = $delta . ':' . $value['value'];
    }
    $this->assertText($setting . '|' . implode('|', $array), t('Values were displayed with expected setting.'));

    // Check the prepare_view steps are invoked.
    $display = array(
      'label' => 'hidden',
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $this->randomName(),
      ),
    );
    $output = field_view_field('test_entity', $this->entity, $this->field_name, $display);
    $view = drupal_render($output);
    $this->drupalSetContent($view);
    $setting = $display['settings']['test_formatter_setting_additional'];
    $this->assertNoText($this->label, t('Label was not displayed.'));
    $this->assertNoText('field_test_field_attach_view_alter', t('Alter not fired.'));
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the instance are
    // used.
    $output = field_view_field('test_entity', $this->entity, $this->field_name, 'teaser');
    $this->drupalSetContent(drupal_render($output));
    $setting = $this->instance['display']['teaser']['settings']['test_formatter_setting'];
    $this->assertText($this->label, t('Label was displayed.'));
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $output = field_view_field('test_entity', $this->entity, $this->field_name, 'unknown_view_mode');
    $this->drupalSetContent(drupal_render($output));
    $setting = $this->instance['display']['default']['settings']['test_formatter_setting'];
    $this->assertText($this->label, t('Label was displayed.'));
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }
  }

  /**
   * Test the field_view_value() function.
   */
  function testFieldViewValue() {
    // No display settings: check that default display settings are used.
    $settings = field_info_formatter_settings('field_test_default');
    $setting = $settings['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED][$delta];
      $output = field_view_value('test_entity', $this->entity, $this->field_name, $item);
      $this->drupalSetContent(drupal_render($output));
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that explicit display settings are used.
    $display = array(
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_multiple'];
    $array = array();
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED][$delta];
      $output = field_view_value('test_entity', $this->entity, $this->field_name, $item, $display);
      $this->drupalSetContent(drupal_render($output));
      $this->assertText($setting . '|0:' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that prepare_view steps are invoked.
    $display = array(
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $this->randomName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_additional'];
    $array = array();
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED][$delta];
      $output = field_view_value('test_entity', $this->entity, $this->field_name, $item, $display);
      $this->drupalSetContent(drupal_render($output));
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the instance are
    // used.
    $setting = $this->instance['display']['teaser']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED][$delta];
      $output = field_view_value('test_entity', $this->entity, $this->field_name, $item, 'teaser');
      $this->drupalSetContent(drupal_render($output));
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $setting = $this->instance['display']['default']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[LANGUAGE_NOT_SPECIFIED][$delta];
      $output = field_view_value('test_entity', $this->entity, $this->field_name, $item, 'unknown_view_mode');
      $this->drupalSetContent(drupal_render($output));
      $this->assertText($setting . '|' . $value['value'], t('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }
  }
}
