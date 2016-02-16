<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\FieldUITest.
 */

namespace Drupal\field\Tests\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Views;

/**
 * Tests the UI of the field field handler.
 *
 * @group field
 * @see \Drupal\field\Plugin\views\field\Field
 */
class FieldUITest extends FieldTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_fieldapi');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  /**
   * A user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser(array('administer views'));
    $this->drupalLogin($this->account);

    $this->setUpFieldStorages(1, 'text');
    $this->setUpFields();
  }

  /**
   * Tests basic field handler settings in the UI.
   */
  public function testHandlerUI() {
    $url = "admin/structure/views/nojs/handler/test_view_fieldapi/default/field/field_name_0";
    $this->drupalGet($url);

    // Tests the available formatter options.
    $result = $this->xpath('//select[@id=:id]/option', array(':id' => 'edit-options-type'));
    $options = array_map(function($item) {
      return (string) $item->attributes()->value[0];
    }, $result);
    // @todo Replace this sort by assertArray once it's in.
    sort($options, SORT_STRING);
    $this->assertEqual($options, array('text_default', 'text_trimmed'), 'The text formatters for a simple text field appear as expected.');

    $this->drupalPostForm(NULL, array('options[type]' => 'text_trimmed'), t('Apply'));

    $this->drupalGet($url);
    $this->assertOptionSelected('edit-options-type', 'text_trimmed');

    $random_number = rand(100, 400);
    $this->drupalPostForm(NULL, array('options[settings][trim_length]' => $random_number), t('Apply'));
    $this->drupalGet($url);
    $this->assertFieldByName('options[settings][trim_length]', $random_number, 'The formatter setting got saved.');

    // Save the view and test whether the settings are saved.
    $this->drupalPostForm('admin/structure/views/view/test_view_fieldapi', array(), t('Save'));
    $view = Views::getView('test_view_fieldapi');
    $view->initHandlers();
    $this->assertEqual($view->field['field_name_0']->options['type'], 'text_trimmed');
    $this->assertEqual($view->field['field_name_0']->options['settings']['trim_length'], $random_number);

    // Ensure that the view depends on the field storage.
    $dependencies = \Drupal::service('config.manager')->findConfigEntityDependents('config', [$this->fieldStorages[0]->getConfigDependencyName()]);
    $this->assertTrue(isset($dependencies['views.view.test_view_fieldapi']), 'The view is dependent on the field storage.');
  }

  /**
   * Tests the basic field handler form when aggregation is enabled.
   */
  public function testHandlerUIAggregation() {
    // Enable aggregation.
    $edit = array('group_by' => '1');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view_fieldapi/default/group_by', $edit, t('Apply'));

    $url = "admin/structure/views/nojs/handler/test_view_fieldapi/default/field/field_name_0";
    $this->drupalGet($url);
    $this->assertResponse(200);

    // Test the click sort column options.
    // Tests the available formatter options.
    $result = $this->xpath('//select[@id=:id]/option', array(':id' => 'edit-options-click-sort-column'));
    $options = array_map(function($item) {
      return (string) $item->attributes()->value[0];
    }, $result);
    sort($options, SORT_STRING);

    $this->assertEqual($options, array('format', 'value'), 'The expected sort field options were found.');
  }

  /**
   * Tests adding a boolean field filter handler.
   */
  public function testBooleanFilterHandler() {
    // Create a boolean field.
    $field_name = 'field_boolean';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $field->save();

    $url = "admin/structure/views/nojs/add-handler/test_view_fieldapi/default/filter";
    $this->drupalPostForm($url, ['name[node__' . $field_name . '.' . $field_name . '_value]' => TRUE], t('Add and configure @handler', array('@handler' => t('filter criteria'))));
    $this->assertResponse(200);
    // Verify that using a boolean field as a filter also results in using the
    // boolean plugin.
    $option = $this->xpath('//label[@for="edit-options-value-1"]');
    $this->assertEqual(t('True'), (string) $option[0]);
    $option = $this->xpath('//label[@for="edit-options-value-0"]');
    $this->assertEqual(t('False'), (string) $option[0]);
  }
}
