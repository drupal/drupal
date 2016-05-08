<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the field display API.
 *
 * @group field
 */
class DisplayApiTest extends FieldKernelTestBase {

  /**
   * The field name to use in this test.
   *
   * @var string
   */
  protected $fieldName;

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
  protected $displayOptions;

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

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  protected function setUp() {
    parent::setUp();

    // Create a field and its storage.
    $this->fieldName = 'test_field';
    $this->label = $this->randomMachineName();
    $this->cardinality = 4;

    $field_storage = array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => $this->cardinality,
    );
    $field = array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->label,
    );

    $this->displayOptions = array(
      'default' => array(
        'type' => 'field_test_default',
        'settings' => array(
          'test_formatter_setting' => $this->randomMachineName(),
        ),
      ),
      'teaser' => array(
        'type' => 'field_test_default',
        'settings' => array(
          'test_formatter_setting' => $this->randomMachineName(),
        ),
      ),
    );

    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($field)->save();
    // Create a display for the default view mode.
    entity_get_display($field['entity_type'], $field['bundle'], 'default')
      ->setComponent($this->fieldName, $this->displayOptions['default'])
      ->save();
    // Create a display for the teaser view mode.
    EntityViewMode::create(array('id' => 'entity_test.teaser', 'targetEntityType' => 'entity_test'))->save();
    entity_get_display($field['entity_type'], $field['bundle'], 'teaser')
      ->setComponent($this->fieldName, $this->displayOptions['teaser'])
      ->save();

    // Create an entity with values.
    $this->values = $this->_generateTestFieldValues($this->cardinality);
    $this->entity = EntityTest::create();
    $this->entity->{$this->fieldName}->setValue($this->values);
    $this->entity->save();
  }

  /**
   * Tests the FieldItemListInterface::view() method.
   */
  function testFieldItemListView() {
    $items = $this->entity->get($this->fieldName);

    \Drupal::service('theme_handler')->install(['classy']);
    \Drupal::service('theme_handler')->setDefault('classy');

    // No display settings: check that default display settings are used.
    $build = $items->view();
    $this->render($build);
    $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('field_test_default');
    $setting = $settings['test_formatter_setting'];
    $this->assertText($this->label, 'Label was displayed.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Display settings: Check hidden field.
    $display = array(
      'label' => 'hidden',
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomMachineName(),
        'alter' => TRUE,
      ),
    );
    $build = $items->view($display);
    $this->render($build);
    $setting = $display['settings']['test_formatter_setting_multiple'];
    $this->assertNoText($this->label, 'Label was not displayed.');
    $this->assertText('field_test_entity_display_build_alter', 'Alter fired, display passed.');
    $this->assertText('entity language is en', 'Language is placed onto the context.');
    $array = array();
    foreach ($this->values as $delta => $value) {
      $array[] = $delta . ':' . $value['value'];
    }
    $this->assertText($setting . '|' . implode('|', $array), 'Values were displayed with expected setting.');

    // Display settings: Check visually_hidden field.
    $display = array(
      'label' => 'visually_hidden',
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomMachineName(),
        'alter' => TRUE,
      ),
    );
    $build = $items->view($display);
    $this->render($build);
    $setting = $display['settings']['test_formatter_setting_multiple'];
    $this->assertRaw('visually-hidden', 'Label was visually hidden.');
    $this->assertText('field_test_entity_display_build_alter', 'Alter fired, display passed.');
    $this->assertText('entity language is en', 'Language is placed onto the context.');
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
        'test_formatter_setting_additional' => $this->randomMachineName(),
      ),
    );
    $build = $items->view($display);
    $this->render($build);
    $setting = $display['settings']['test_formatter_setting_additional'];
    $this->assertNoText($this->label, 'Label was not displayed.');
    $this->assertNoText('field_test_entity_display_build_alter', 'Alter not fired.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the display object
    // are used.
    $build = $items->view('teaser');
    $this->render($build);
    $setting = $this->displayOptions['teaser']['settings']['test_formatter_setting'];
    $this->assertText($this->label, 'Label was displayed.');
    foreach ($this->values as $delta => $value) {
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $build = $items->view('unknown_view_mode');
    $this->render($build);
    $setting = $this->displayOptions['default']['settings']['test_formatter_setting'];
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
      $item = $this->entity->{$this->fieldName}[$delta];
      $build = $item->view();
      $this->render($build);
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that explicit display settings are used.
    $display = array(
      'type' => 'field_test_multiple',
      'settings' => array(
        'test_formatter_setting_multiple' => $this->randomMachineName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_multiple'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->fieldName}[$delta];
      $build = $item->view($display);
      $this->render($build);
      $this->assertText($setting . '|0:' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Check that prepare_view steps are invoked.
    $display = array(
      'type' => 'field_test_with_prepare_view',
      'settings' => array(
        'test_formatter_setting_additional' => $this->randomMachineName(),
      ),
    );
    $setting = $display['settings']['test_formatter_setting_additional'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->fieldName}[$delta];
      $build = $item->view($display);
      $this->render($build);
      $this->assertText($setting . '|' . $value['value'] . '|' . ($value['value'] + 1), format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // View mode: check that display settings specified in the field are used.
    $setting = $this->displayOptions['teaser']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->fieldName}[$delta];
      $build = $item->view('teaser');
      $this->render($build);
      $this->assertText($setting . '|' . $value['value'], format_string('Value @delta was displayed with expected setting.', array('@delta' => $delta)));
    }

    // Unknown view mode: check that display settings for 'default' view mode
    // are used.
    $setting = $this->displayOptions['default']['settings']['test_formatter_setting'];
    foreach ($this->values as $delta => $value) {
      $item = $this->entity->{$this->fieldName}[$delta];
      $build = $item->view('unknown_view_mode');
      $this->render($build);
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
        'test_empty_string' => '**EMPTY FIELD**' . $this->randomMachineName(),
      ),
    );
    // $this->entity is set by the setUp() method and by default contains 4
    // numeric values.  We only want to test the display of this one field.
    $build = $this->entity->get($this->fieldName)->view($display);
    $this->render($build);
    // The test field by default contains values, so should not display the
    // default "empty" text.
    $this->assertNoText($display['settings']['test_empty_string']);

    // Now remove the values from the test field and retest.
    $this->entity->{$this->fieldName} = array();
    $this->entity->save();
    $build = $this->entity->get($this->fieldName)->view($display);
    $this->render($build);
    // This time, as the field values have been removed, we *should* show the
    // default "empty" text.
    $this->assertText($display['settings']['test_empty_string']);
  }

}
