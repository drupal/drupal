<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Views;

/**
 * Tests the UI of the field handler.
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
  public static $testViews = ['test_view_fieldapi'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['field_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->account);

    $this->setUpFieldStorages(1, 'text');
    $this->setUpFields();
  }

  /**
   * Tests basic field handler settings in the UI.
   */
  public function testHandlerUI(): void {
    $url = "admin/structure/views/nojs/handler/test_view_fieldapi/default/field/field_name_0";
    $this->drupalGet($url);

    // Tests the available formatter options.
    $options = $this->assertSession()->selectExists('edit-options-type')->findAll('css', 'option');
    $options = array_map(function ($item) {
      return $item->getValue();
    }, $options);
    $this->assertEqualsCanonicalizing(['text_default', 'text_trimmed'], $options);

    $this->submitForm(['options[type]' => 'text_trimmed'], 'Apply');

    $this->drupalGet($url);
    $this->assertTrue($this->assertSession()->optionExists('edit-options-type', 'text_trimmed')->isSelected());

    $random_number = rand(100, 400);
    $this->submitForm(['options[settings][trim_length]' => $random_number], 'Apply');
    $this->drupalGet($url);
    $this->assertSession()->fieldValueEquals('options[settings][trim_length]', $random_number);

    // Save the view and test whether the settings are saved.
    $this->drupalGet('admin/structure/views/view/test_view_fieldapi');
    $this->submitForm([], 'Save');
    $view = Views::getView('test_view_fieldapi');
    $view->initHandlers();
    $this->assertEquals('text_trimmed', $view->field['field_name_0']->options['type']);
    $this->assertEquals($random_number, $view->field['field_name_0']->options['settings']['trim_length']);

    // Now change the formatter back to 'default' which doesn't have any
    // settings. We want to ensure that the settings are empty then.
    $edit['options[type]'] = 'text_default';
    $this->drupalGet('admin/structure/views/nojs/handler/test_view_fieldapi/default/field/field_name_0');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view_fieldapi');
    $this->submitForm([], 'Save');
    $view = Views::getView('test_view_fieldapi');
    $view->initHandlers();
    $this->assertEquals('text_default', $view->field['field_name_0']->options['type']);
    $this->assertEquals([], $view->field['field_name_0']->options['settings']);

    // Ensure that the view depends on the field storage.
    $dependencies = \Drupal::service('config.manager')->findConfigEntityDependencies('config', [$this->fieldStorages[0]->getConfigDependencyName()]);
    $this->assertTrue(isset($dependencies['views.view.test_view_fieldapi']), 'The view is dependent on the field storage.');
  }

  /**
   * Tests the basic field handler form when aggregation is enabled.
   */
  public function testHandlerUIAggregation(): void {
    // Enable aggregation.
    $edit = ['group_by' => '1'];
    $this->drupalGet('admin/structure/views/nojs/display/test_view_fieldapi/default/group_by');
    $this->submitForm($edit, 'Apply');

    $url = "admin/structure/views/nojs/handler/test_view_fieldapi/default/field/field_name_0";
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test the click sort column options.
    // Tests the available formatter options.
    $options = $this->assertSession()->selectExists('edit-options-click-sort-column')->findAll('css', 'option');
    $options = array_map(function ($item) {
      return $item->getValue();
    }, $options);
    $this->assertEqualsCanonicalizing(['format', 'value'], $options);
  }

  /**
   * Tests adding a boolean field filter handler.
   */
  public function testBooleanFilterHandler(): void {
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
    $this->drupalGet($url);
    $this->submitForm([
      'name[node__' . $field_name . '.' . $field_name . '_value]' => TRUE,
    ], 'Add and configure filter criteria');
    $this->assertSession()->statusCodeEquals(200);
    // Verify that using a boolean field as a filter also results in using the
    // boolean plugin.
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-options-value-1"]', 'True');
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-options-value-0"]', 'False');

    // Expose the filter and see if the 'Any' option is added and if we can save
    // it.
    $this->submitForm([], 'Expose filter');
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-options-value-all"]', '- Any -');
    $this->submitForm(['options[value]' => 'All', 'options[expose][required]' => FALSE], 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('/admin/structure/views/nojs/handler/test_view_fieldapi/default/filter/field_boolean_value');
    $this->assertSession()->checkboxChecked('edit-options-value-all');
  }

}
