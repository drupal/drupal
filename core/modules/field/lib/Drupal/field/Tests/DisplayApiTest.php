<?php

/**
 * @file
 * Definition of Drupal\field\Tests\DisplayApiTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the field display API.
 */
class DisplayApiTest extends FieldUnitTestBase {

  /**
   * The field name to use in this test.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The field label to use in this test.
   *
   * @var string
   */
  protected $label;

  /**
   * The field cardinality to use in this test.
   *
   * @var number
   */
  protected $cardinality;

  /**
   * The field display options to use in this test.
   *
   * @var array
   */
  protected $display_options;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * An array of random values, in the format expected for field values.
   *
   * @var array
   */
  protected $values;

  public static function getInfo() {
    return array(
      'name' => 'Field Display API tests',
      'description' => 'Test the display API.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a field and instance.
    $this->field_name = 'test_field';
    $this->label = $this->randomName();
    $this->cardinality = 4;

    $field = array(
      'name' => $this->field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => $this->cardinality,
    );
    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->label,
    );

    $this->display_options = array(
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
    );

    entity_create('field_config', $field)->save();
    entity_create('field_instance_config', $instance)->save();
    // Create a display for the default view mode.
    entity_get_display($instance['entity_type'], $instance['bundle'], 'default')
      ->setComponent($this->field_name, $this->display_options['default'])
      ->save();
    // Create a display for the teaser view mode.
    entity_create('view_mode', array('id' =>  'entity_test.teaser', 'targetEntityType' => 'entity_test'))->save();
    entity_get_display($instance['entity_type'], $instance['bundle'], 'teaser')
      ->setComponent($this->field_name, $this->display_options['teaser'])
      ->save();

    // Create an entity with values.
    $this->values = $this->_generateTestFieldValues($this->cardinality);
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->field_name}->setValue($this->values);
    $this->entity->save();
  }

  /**
   * Tests the FieldItemListInterface::view() method.
   */
  function testFieldItemListView() {
    $items = $this->entity->get($this->field_name);

    // No display settings: check that default display settings are used.
    $output = $items->view();
    $this->content = drupal_render($output);
    $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('field_test_default');
    $setting = $settings['test_formatter_setting'];
    $this->assertText($this->label, 'Label was displayed.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
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
    $output = $items->view($display);
    $this->content = drupal_render($output);
    $setting = $display['settings']['test_formatter_setting_multiple'];
    $this->assertNoText($this->label, 'Label was not displayed.');
    $this->assertText('field_test_entity_display_build_alter', 'Alter fired, display passed.');
    $this->assertText('entity language is ' . Language::LANGCODE_NOT_SPECIFIED, 'Language is placed onto the context.');
    $array = array();
    foreach ($this->values as $delta => $value) {
      $array[] = $delta . ':' . $value['value'];
    }
    $this->assertText($setting . '|' . implode('|', $array), 'Values were displayed with expected setting.');

    // Check the prepare_view steps are invoked.
    $display = array(
      'label' => 'hidden',
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $this->randomName(),
      ),
    );
    $output = $items->view($display);
    $view = drupal_render($output);
    $this->content = $view;
    $setting = $display['settings']['test_formatter_setting_additional'];
    $this->assertNoText($this->label, 'Label was not displayed.');
    $this->assertNoText('field_test_entity_display_build_alter', 'Alter not fired.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the display object
    // are used.
    $output = $items->view('teaser');
    $this->content = drupal_render($output);
    $setting = $this->display_options['teaser']['settings']['test_formatter_setting'];
    $this->assertText($this->label, 'Label was displayed.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $output = $items->view('unknown_view_mode');
    $this->content = drupal_render($output);
    $setting = $this->display_options['default']['settings']['test_formatter_setting'];
    $this->assertText($this->label, 'Label was displayed.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }
  }

  /**
   * Tests the FieldItemInterface::view() method.
   */
  function testFieldItemView() {
    // No display settings: check that default display settings are used.
    $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('field_test_default');
    $setting = $settings['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[$delta];
      $output = $item->view();
      $this->content = drupal_render($output);
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that explicit display settings are used.
    $display = array(
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_multiple'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[$delta];
      $output = $item->view($display);
      $this->content = drupal_render($output);
      $this->assertText($setting . '|0:' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that prepare_view steps are invoked.
    $display = array(
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $this->randomName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_additional'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[$delta];
      $output = $item->view($display);
      $this->content = drupal_render($output);
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the instance are
    // used.
    $setting = $this->display_options['teaser']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[$delta];
      $output = $item->view('teaser');
      $this->content = drupal_render($output);
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $setting = $this->display_options['default']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->field_name}[$delta];
      $output = $item->view('unknown_view_mode');
      $this->content = drupal_render($output);
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }
  }

  /**
   * Tests that the prepareView() formatter method still fires for empty values.
   */
  function testFieldEmpty() {
    // Uses \Drupal\field_test\Plugin\Field\FieldFormatter\TestFieldEmptyFormatter.
    $display = array(
      'label' => 'hidden',
      'type' => 'field_empty_test',
      'settings' => array(
        'test_empty_string' => '**EMPTY FIELD**' . $this->randomName(),
      ),
    );
    // $this->entity is set by the setUp() method and by default contains 4
    // numeric values.  We only want to test the display of this one field.
    $output = $this->entity->get($this->field_name)->view($display);
    $view = drupal_render($output);
    $this->content = $view;
    // The test field by default contains values, so should not display the
    // default "empty" text.
    $this->assertNoText($display['settings']['test_empty_string']);

    // Now remove the values from the test field and retest.
    $this->entity->{$this->field_name} = array();
    $this->entity->save();
    $output = $this->entity->get($this->field_name)->view($display);
    $view = drupal_render($output);
    $this->content = $view;
    // This time, as the field values have been removed, we *should* show the
    // default "empty" text.
    $this->assertText($display['settings']['test_empty_string']);
  }
}
