<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBaseFieldDisplay;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field form handling.
 *
 * @group field
 */
class MultipleWidgetFormTest extends FieldTestBase {

  /**
   * Modules to install.
   *
   * Locale is installed so that TranslatableMarkup actually does something.
   *
   * @var array
   */
  protected static $modules = [
    'field_test',
    'options',
    'entity_test',
    'locale',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of values defining a field multiple.
   *
   * @var array
   */
  protected $fieldStorageMultiple;

  /**
   * An array of values defining a field.
   *
   * @var array
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer entity_test fields',
    ]);
    $this->drupalLogin($web_user);

    $this->fieldStorageMultiple = [
      'field_name' => 'field_multiple',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => 4,
    ];

    $this->field = [
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => [
        'test_field_setting' => $this->randomMachineName(),
      ],
    ];
  }

  /**
   * Tests widgets handling multiple values.
   */
  public function testFieldFormMultipleWidget(): void {
    // Create a field with fixed cardinality, configure the form to use a
    // "multiple" widget.
    $field_storage = $this->fieldStorageMultiple;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    $form = \Drupal::service('entity_display.repository')->getFormDisplay($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name, [
        'type' => 'test_field_widget_multiple',
      ]);
    $form->save();
    $session = $this->assertSession();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals($field_name, '');

    // Create entity with three values.
    $edit = [
      $field_name => '1, 2, 3',
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];

    // Check that the values were saved.
    $entity_init = EntityTest::load($id);
    $this->assertFieldValues($entity_init, $field_name, [1, 2, 3]);

    // Display the form, check that the values are correctly filled in.
    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $this->assertSession()->fieldValueEquals($field_name, '1, 2, 3');

    // Submit the form with more values than the field accepts.
    $edit = [$field_name => '1, 2, 3, 4, 5'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('this field cannot hold more than 4 values');
    // Check that the field values were not submitted.
    $this->assertFieldValues($entity_init, $field_name, [1, 2, 3]);

    // Check that Attributes are rendered on the multivalue container if it is
    // a multiple widget form.
    $form->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete',
    ])
      ->save();
    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $name = str_replace('_', '-', $field_name);
    $session->responseContains('data-drupal-selector="edit-' . $name . '"');
  }

  /**
   * Tests the form display of the label for multi-value fields.
   */
  public function testLabelOnMultiValueFields(): void {
    $user = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($user);

    // Ensure that the 'bar' bundle exists, to avoid config validation errors.
    EntityTestHelper::createBundle('bar', entity_type: 'entity_test_base_field_display');

    FieldStorageConfig::create([
      'entity_type' => 'entity_test_base_field_display',
      'field_name' => 'foo',
      'type' => 'text',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_base_field_display',
      'bundle' => 'bar',
      'field_name' => 'foo',
      // Set a dangerous label to test XSS filtering.
      'label' => "<script>alert('a configurable field');</script>",
    ])->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'bar',
      'mode' => 'default',
    ])->setComponent('foo', ['type' => 'text_textfield'])->enable()->save();

    $entity = EntityTestBaseFieldDisplay::create(['type' => 'bar']);
    $entity->save();

    $this->drupalGet('entity_test_base_field_display/manage/' . $entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('A field with multiple values');
    // Test if labels were XSS filtered.
    $this->assertSession()->assertEscaped("<script>alert('a configurable field');</script>");
  }

  /**
   * Tests hook_field_widget_complete_form_alter().
   */
  public function testFieldFormMultipleWidgetAlter(): void {
    $this->widgetAlterTest('hook_field_widget_complete_form_alter', 'test_field_widget_multiple');
  }

  /**
   * Tests hook_field_widget_complete_form_alter() with single value elements.
   */
  public function testFieldFormMultipleWidgetAlterSingleValues(): void {
    $this->widgetAlterTest('hook_field_widget_complete_form_alter', 'test_field_widget_multiple_single_value');
  }

  /**
   * Tests hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  public function testFieldFormMultipleWidgetTypeAlter(): void {
    $this->widgetAlterTest('hook_field_widget_complete_WIDGET_TYPE_form_alter', 'test_field_widget_multiple');
  }

  /**
   * Tests hook_field_widget_complete_WIDGET_TYPE_form_alter() with single value elements.
   */
  public function testFieldFormMultipleWidgetTypeAlterSingleValues(): void {
    $this->widgetAlterTest('hook_field_widget_complete_WIDGET_TYPE_form_alter', 'test_field_widget_multiple_single_value');
  }

  /**
   * Tests widget alter hooks for a given hook name.
   */
  protected function widgetAlterTest($hook, $widget) {
    // Create a field with fixed cardinality, configure the form to use a
    // "multiple" widget.
    $field_storage = $this->fieldStorageMultiple;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();

    // Set a flag in state so that the hook implementations will run.
    \Drupal::state()->set("field_test.widget_alter_test", [
      'hook' => $hook,
      'field_name' => $field_name,
      'widget' => $widget,
    ]);
    \Drupal::service('entity_display.repository')->getFormDisplay($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name, [
        'type' => $widget,
      ])
      ->save();

    // We need to rebuild hook information after setting the component through
    // the API.
    $this->rebuildAll();

    $this->drupalGet('entity_test/add');
    $this->assertSession()->pageTextMatchesCount(1, '/From ' . $hook . '.* prefix on ' . $field_name . ' parent element\./');
    if ($widget === 'test_field_widget_multiple_single_value') {
      $suffix_text = "From $hook(): suffix on $field_name child element.";
      $this->assertEquals($field_storage['cardinality'], substr_count($this->getTextContent(), $suffix_text), "'$suffix_text' was found {$field_storage['cardinality']} times  using widget $widget");
    }
  }

}
