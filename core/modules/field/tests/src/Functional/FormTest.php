<?php

namespace Drupal\Tests\field\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBaseFieldDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field form handling.
 *
 * @group field
 */
class FormTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * Locale is installed so that TranslatableMarkup actually does something.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_test',
    'options',
    'entity_test',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of values defining a field single.
   *
   * @var array
   */
  protected $fieldStorageSingle;

  /**
   * An array of values defining a field multiple.
   *
   * @var array
   */
  protected $fieldStorageMultiple;

  /**
   * An array of values defining a field with unlimited cardinality.
   *
   * @var array
   */
  protected $fieldStorageUnlimited;

  /**
   * An array of values defining a field.
   *
   * @var array
   */
  protected $field;

  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['view test entity', 'administer entity_test content']);
    $this->drupalLogin($web_user);

    $this->fieldStorageSingle = [
      'field_name' => 'field_single',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ];
    $this->fieldStorageMultiple = [
      'field_name' => 'field_multiple',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => 4,
    ];
    $this->fieldStorageUnlimited = [
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
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

  public function testFieldFormSingle() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Create token value expected for description.
    $token_description = Html::escape($this->config('system.site')->get('name')) . '_description';
    $this->assertText($token_description, 'Token replacement for description is displayed');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNoField("{$field_name}[1][value]", 'No extraneous widget is displayed');

    // Check that hook_field_widget_form_alter() does not believe this is the
    // default value form.
    $this->assertNoText('From hook_field_widget_form_alter(): Default form is true.', 'Not default value form in hook_field_widget_form_alter().');
    // Check that hook_field_widget_form_alter() does not believe this is the
    // default value form.
    $this->assertNoText('From hook_field_widget_multivalue_form_alter(): Default form is true.', 'Not default value form in hook_field_widget_form_alter().');

    // Submit with invalid value (field-level validation).
    $edit = [
      "{$field_name}[0][value]" => -1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name does not accept the value -1.', ['%name' => $this->field['label']]), 'Field validation fails with invalid input.');
    // TODO : check that the correct field is flagged for error.

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]), 'Entity was created');
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was saved');

    // Display edit form.
    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $this->assertFieldByName("{$field_name}[0][value]", $value, 'Widget is displayed with the correct default value');
    $this->assertNoField("{$field_name}[1][value]", 'No extraneous widget is displayed');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', ['@id' => $id]), 'Entity was updated');
    $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache([$id]);
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was updated');

    // Empty the field.
    $value = '';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm('entity_test/manage/' . $id . '/edit', $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', ['@id' => $id]), 'Entity was updated');
    $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache([$id]);
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->{$field_name}->isEmpty(), 'Field was emptied');
  }

  /**
   * Tests field widget default values on entity forms.
   */
  public function testFieldFormDefaultValue() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $default = rand(1, 127);
    $this->field['default_value'] = [['value' => $default]];
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    // Test that the default value is displayed correctly.
    $this->assertFieldByXpath("//input[@name='{$field_name}[0][value]' and @value='$default']");

    // Try to submit an empty value.
    $edit = [
      "{$field_name}[0][value]" => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]), 'Entity was created.');
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->{$field_name}->isEmpty(), 'Field is now empty.');
  }

  public function testFieldFormSingleRequired() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $this->field['required'] = TRUE;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Submit with missing required value.
    $edit = [];
    $this->drupalPostForm('entity_test/add', $edit, t('Save'));
    $this->assertRaw(t('@name field is required.', ['@name' => $this->field['label']]), 'Required field with no value fails validation');

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]), 'Entity was created');
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was saved');

    // Edit with missing required value.
    $value = '';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm('entity_test/manage/' . $id . '/edit', $edit, t('Save'));
    $this->assertRaw(t('@name field is required.', ['@name' => $this->field['label']]), 'Required field with no value fails validation');
  }

  public function testFieldFormUnlimited() {
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Display creation form -> 1 widget.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget 1 is displayed');
    $this->assertNoField("{$field_name}[1][value]", 'No extraneous widget is displayed');

    // Check if aria-describedby attribute is placed on multiple value widgets.
    $elements = $this->xpath('//table[@id="field-unlimited-values" and @aria-describedby="edit-field-unlimited--description"]');
    $this->assertTrue(isset($elements[0]), t('aria-describedby attribute is properly placed on multiple value widgets.'));

    // Press 'add more' button -> 2 widgets.
    $this->drupalPostForm(NULL, [], t('Add another item'));
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$field_name}[1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$field_name}[2][value]", 'No extraneous widget is displayed');
    // TODO : check that non-field inputs are preserved ('title'), etc.

    // Yet another time so that we can play with more values -> 3 widgets.
    $this->drupalPostForm(NULL, [], t('Add another item'));

    // Prepare values and weights.
    $count = 3;
    $delta_range = $count - 1;
    $values = $weights = $pattern = $expected_values = [];
    $edit = [];
    for ($delta = 0; $delta <= $delta_range; $delta++) {
      // Assign unique random values and weights.
      do {
        $value = mt_rand(1, 127);
      } while (in_array($value, $values));
      do {
        $weight = mt_rand(-$delta_range, $delta_range);
      } while (in_array($weight, $weights));
      $edit["{$field_name}[$delta][value]"] = $value;
      $edit["{$field_name}[$delta][_weight]"] = $weight;
      // We'll need three slightly different formats to check the values.
      $values[$delta] = $value;
      $weights[$delta] = $weight;
      $field_values[$weight]['value'] = (string) $value;
      $pattern[$weight] = "<input [^>]*value=\"$value\" [^>]*";
    }

    // Press 'add more' button -> 4 widgets
    $this->drupalPostForm(NULL, $edit, t('Add another item'));
    for ($delta = 0; $delta <= $delta_range; $delta++) {
      $this->assertFieldByName("{$field_name}[$delta][value]", $values[$delta], "Widget $delta is displayed and has the right value");
      $this->assertFieldByName("{$field_name}[$delta][_weight]", $weights[$delta], "Widget $delta has the right weight");
    }
    ksort($pattern);
    $pattern = implode('.*', array_values($pattern));
    $this->assertPattern("|$pattern|s", 'Widgets are displayed in the correct order');
    $this->assertFieldByName("{$field_name}[$delta][value]", '', "New widget is displayed");
    $this->assertFieldByName("{$field_name}[$delta][_weight]", $delta, "New widget has the right weight");
    $this->assertNoField("{$field_name}[" . ($delta + 1) . '][value]', 'No extraneous widget is displayed');

    // Submit the form and create the entity.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]), 'Entity was created');
    $entity = EntityTest::load($id);
    ksort($field_values);
    $field_values = array_values($field_values);
    $this->assertIdentical($entity->{$field_name}->getValue(), $field_values, 'Field values were saved in the correct order');

    // Display edit form: check that the expected number of widgets is
    // displayed, with correct values change values, reorder, leave an empty
    // value in the middle.
    // Submit: check that the entity is updated with correct values
    // Re-submit: check that the field can be emptied.

    // Test with several multiple fields in a form
  }

  /**
   * Tests the position of the required label.
   */
  public function testFieldFormUnlimitedRequired() {
    $field_name = $this->fieldStorageUnlimited['field_name'];
    $this->field['field_name'] = $field_name;
    $this->field['required'] = TRUE;
    FieldStorageConfig::create($this->fieldStorageUnlimited)->save();
    FieldConfig::create($this->field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Display creation form -> 1 widget.
    $this->drupalGet('entity_test/add');
    // Check that the Required symbol is present for the multifield label.
    $element = $this->xpath('//h4[contains(@class, "label") and contains(@class, "js-form-required") and contains(text(), :value)]', [':value' => $this->field['label']]);
    $this->assertTrue(isset($element[0]), 'Required symbol added field label.');
    // Check that the label of the field input is visually hidden and contains
    // the field title and an indication of the delta for a11y.
    $element = $this->xpath('//label[@for=:for and contains(@class, "visually-hidden") and contains(text(), :value)]', [':for' => 'edit-field-unlimited-0-value', ':value' => $this->field['label'] . ' (value 1)']);
    $this->assertTrue(isset($element[0]), 'Required symbol not added for field input.');
  }

  /**
   * Tests widget handling of multiple required radios.
   */
  public function testFieldFormMultivalueWithRequiredRadio() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a multivalue test field.
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($this->field)->save();
    $display_repository->getFormDisplay($this->field['entity_type'], $this->field['bundle'])
      ->setComponent($field_name)
      ->save();

    // Add a required radio field.
    FieldStorageConfig::create([
      'field_name' => 'required_radio_test',
      'entity_type' => 'entity_test',
      'type' => 'list_string',
      'settings' => [
        'allowed_values' => ['yes' => 'yes', 'no' => 'no'],
      ],
    ])->save();
    $field = [
      'field_name' => 'required_radio_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'required' => TRUE,
    ];
    FieldConfig::create($field)->save();
    $display_repository->getFormDisplay($field['entity_type'], $field['bundle'])
      ->setComponent($field['field_name'], [
        'type' => 'options_buttons',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Press the 'Add more' button.
    $this->drupalPostForm(NULL, [], t('Add another item'));

    // Verify that no error is thrown by the radio element.
    $this->assertNoFieldByXpath('//div[contains(@class, "error")]', FALSE, 'No error message is displayed.');

    // Verify that the widget is added.
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$field_name}[1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$field_name}[2][value]", 'No extraneous widget is displayed');
  }

  /**
   * Tests widgets handling multiple values.
   */
  public function testFieldFormMultipleWidget() {
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
    $this->assertFieldByName($field_name, '', 'Widget is displayed.');

    // Create entity with three values.
    $edit = [
      $field_name => '1, 2, 3',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];

    // Check that the values were saved.
    $entity_init = EntityTest::load($id);
    $this->assertFieldValues($entity_init, $field_name, [1, 2, 3]);

    // Display the form, check that the values are correctly filled in.
    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $this->assertFieldByName($field_name, '1, 2, 3', 'Widget is displayed.');

    // Submit the form with more values than the field accepts.
    $edit = [$field_name => '1, 2, 3, 4, 5'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('this field cannot hold more than 4 values', 'Form validation failed.');
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
   * Tests fields with no 'edit' access.
   */
  public function testFieldFormAccess() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $entity_type = 'entity_test_rev';
    // Create a "regular" field.
    $field_storage = $this->fieldStorageSingle;
    $field_storage['entity_type'] = $entity_type;
    $field_name = $field_storage['field_name'];
    $field = $this->field;
    $field['field_name'] = $field_name;
    $field['entity_type'] = $entity_type;
    $field['bundle'] = $entity_type;
    FieldStorageConfig::create($field_storage)->save();
    FieldConfig::create($field)->save();
    $display_repository->getFormDisplay($entity_type, $entity_type)
      ->setComponent($field_name)
      ->save();

    // Create a field with no edit access. See
    // field_test_entity_field_access().
    $field_storage_no_access = [
      'field_name' => 'field_no_edit_access',
      'entity_type' => $entity_type,
      'type' => 'test_field',
    ];
    $field_name_no_access = $field_storage_no_access['field_name'];
    $field_no_access = [
      'field_name' => $field_name_no_access,
      'entity_type' => $entity_type,
      'bundle' => $entity_type,
      'default_value' => [0 => ['value' => 99]],
    ];
    FieldStorageConfig::create($field_storage_no_access)->save();
    FieldConfig::create($field_no_access)->save();
    $display_repository->getFormDisplay($field_no_access['entity_type'], $field_no_access['bundle'])
      ->setComponent($field_name_no_access)
      ->save();

    // Test that the form structure includes full information for each delta
    // apart from #access.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['id' => 0, 'revision_id' => 0]);

    $display = $display_repository->getFormDisplay($entity_type, $entity_type);
    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $this->assertFalse($form[$field_name_no_access]['#access'], 'Field #access is FALSE for the field without edit access.');

    // Display creation form.
    $this->drupalGet($entity_type . '/add');
    $this->assertNoFieldByName("{$field_name_no_access}[0][value]", '', 'Widget is not displayed if field access is denied.');

    // Create entity.
    $edit = [
      "{$field_name}[0][value]" => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match("|$entity_type/manage/(\d+)|", $this->getUrl(), $match);
    $id = $match[1];

    // Check that the default value was saved.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $entity = $storage->load($id);
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'Default value was saved for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 1, 'Entered value vas saved for the field with edit access.');

    // Create a new revision.
    $edit = [
      "{$field_name}[0][value]" => 2,
      'revision' => TRUE,
    ];
    $this->drupalPostForm($entity_type . '/manage/' . $id . '/edit', $edit, t('Save'));

    // Check that the new revision has the expected values.
    $storage->resetCache([$id]);
    $entity = $storage->load($id);
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 2, 'New revision has the expected value for the field with edit access.');

    // Check that the revision is also saved in the revisions table.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->loadRevision($entity->getRevisionId());
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 2, 'New revision has the expected value for the field with edit access.');
  }

  /**
   * Tests hiding a field in a form.
   */
  public function testHiddenField() {
    $entity_type = 'entity_test_rev';
    $field_storage = $this->fieldStorageSingle;
    $field_storage['entity_type'] = $entity_type;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $this->field['default_value'] = [0 => ['value' => 99]];
    $this->field['entity_type'] = $entity_type;
    $this->field['bundle'] = $entity_type;
    FieldStorageConfig::create($field_storage)->save();
    $this->field = FieldConfig::create($this->field);
    $this->field->save();
    // We explicitly do not assign a widget in a form display, so the field
    // stays hidden in forms.

    // Display the entity creation form.
    $this->drupalGet($entity_type . '/add');

    // Create an entity and test that the default value is assigned correctly to
    // the field that uses the hidden widget.
    $this->assertNoField("{$field_name}[0][value]", 'The field does not appear in the form');
    $this->drupalPostForm(NULL, [], t('Save'));
    preg_match('|' . $entity_type . '/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test_rev @id has been created.', ['@id' => $id]), 'Entity was created');
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);

    $entity = $storage->load($id);
    $this->assertEqual($entity->{$field_name}->value, 99, 'Default value was saved');

    // Update the field to remove the default value, and switch to the default
    // widget.
    $this->field->setDefaultValue([]);
    $this->field->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $this->field->getTargetBundle())
      ->setComponent($this->field->getName(), [
        'type' => 'test_field_widget',
      ])
      ->save();

    // Display edit form.
    $this->drupalGet($entity_type . '/manage/' . $id . '/edit');
    $this->assertFieldByName("{$field_name}[0][value]", 99, 'Widget is displayed with the correct default value');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = ["{$field_name}[0][value]" => $value];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test_rev @id has been updated.', ['@id' => $id]), 'Entity was updated');
    $storage->resetCache([$id]);
    $entity = $storage->load($id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was updated');

    // Set the field back to hidden.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $this->field->getTargetBundle())
      ->removeComponent($this->field->getName())
      ->save();

    // Create a new revision.
    $edit = ['revision' => TRUE];
    $this->drupalPostForm($entity_type . '/manage/' . $id . '/edit', $edit, t('Save'));

    // Check that the expected value has been carried over to the new revision.
    $storage->resetCache([$id]);
    $entity = $storage->load($id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'New revision has the expected value for the field with the Hidden widget');
  }

  /**
   * Tests the form display of the label for multi-value fields.
   */
  public function testLabelOnMultiValueFields() {
    $user = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($user);

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
    $this->assertText('A field with multiple values');
    // Test if labels were XSS filtered.
    $this->assertEscaped("<script>alert('a configurable field');</script>");
  }

  /**
   * Tests hook_field_widget_multivalue_form_alter().
   */
  public function testFieldFormMultipleWidgetAlter() {
    $this->widgetAlterTest('hook_field_widget_multivalue_form_alter', 'test_field_widget_multiple');
  }

  /**
   * Tests hook_field_widget_multivalue_form_alter() with single value elements.
   */
  public function testFieldFormMultipleWidgetAlterSingleValues() {
    $this->widgetAlterTest('hook_field_widget_multivalue_form_alter', 'test_field_widget_multiple_single_value');
  }

  /**
   * Tests hook_field_widget_multivalue_WIDGET_TYPE_form_alter().
   */
  public function testFieldFormMultipleWidgetTypeAlter() {
    $this->widgetAlterTest('hook_field_widget_multivalue_WIDGET_TYPE_form_alter', 'test_field_widget_multiple');
  }

  /**
   * Tests hook_field_widget_multivalue_WIDGET_TYPE_form_alter() with single value elements.
   */
  public function testFieldFormMultipleWidgetTypeAlterSingleValues() {
    $this->widgetAlterTest('hook_field_widget_multivalue_WIDGET_TYPE_form_alter', 'test_field_widget_multiple_single_value');
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
    $this->assertUniqueText("From $hook(): prefix on $field_name parent element.");
    if ($widget === 'test_field_widget_multiple_single_value') {
      $suffix_text = "From $hook(): suffix on $field_name child element.";
      $this->assertEqual($field_storage['cardinality'], substr_count($this->getTextContent(), $suffix_text), "'$suffix_text' was found {$field_storage['cardinality']} times  using widget $widget");
    }
  }

}
