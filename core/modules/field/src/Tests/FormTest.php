<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FormTest.
 */

namespace Drupal\field\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
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
   * @var array
   */
  public static $modules = array('node', 'field_test', 'options', 'entity_test');

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

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($web_user);

    $this->fieldStorageSingle = array(
      'field_name' => 'field_single',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $this->fieldStorageMultiple = array(
      'field_name' => 'field_multiple',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => 4,
    );
    $this->fieldStorageUnlimited = array(
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );

    $this->field = array(
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => array(
        'test_field_setting' => $this->randomMachineName(),
      ),
    );
  }

  function testFieldFormSingle() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
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

    // Submit with invalid value (field-level validation).
    $edit = array(
      "{$field_name}[0][value]" => -1
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name does not accept the value -1.', array('%name' => $this->field['label'])), 'Field validation fails with invalid input.');
    // TODO : check that the correct field is flagged for error.

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was saved');

    // Display edit form.
    $this->drupalGet('entity_test/manage/' . $id);
    $this->assertFieldByName("{$field_name}[0][value]", $value, 'Widget is displayed with the correct default value');
    $this->assertNoField("{$field_name}[1][value]", 'No extraneous widget is displayed');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', array('@id' => $id)), 'Entity was updated');
    $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was updated');

    // Empty the field.
    $value = '';
    $edit = array(
      "{$field_name}[0][value]" => $value
    );
    $this->drupalPostForm('entity_test/manage/' . $id, $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', array('@id' => $id)), 'Entity was updated');
    $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->{$field_name}->isEmpty(), 'Field was emptied');
  }

  /**
   * Tests field widget default values on entity forms.
   */
  function testFieldFormDefaultValue() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $default = rand(1, 127);
    $this->field['default_value'] = array(array('value' => $default));
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    // Test that the default value is displayed correctly.
    $this->assertFieldByXpath("//input[@name='{$field_name}[0][value]' and @value='$default']");

    // Try to submit an empty value.
    $edit = array(
      "{$field_name}[0][value]" => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created.');
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->{$field_name}->isEmpty(), 'Field is now empty.');
  }

  function testFieldFormSingleRequired() {
    $field_storage = $this->fieldStorageSingle;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $this->field['required'] = TRUE;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Submit with missing required value.
    $edit = array();
    $this->drupalPostForm('entity_test/add', $edit, t('Save'));
    $this->assertRaw(t('@name field is required.', array('@name' => $this->field['label'])), 'Required field with no value fails validation');

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was saved');

    // Edit with missing required value.
    $value = '';
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm('entity_test/manage/' . $id, $edit, t('Save'));
    $this->assertRaw(t('@name field is required.', array('@name' => $this->field['label'])), 'Required field with no value fails validation');
  }

//  function testFieldFormMultiple() {
//    $this->field = $this->field_multiple;
//    $field_name = $this->field['field_name'];
//    $this->instance['field_name'] = $field_name;
//    entity_create('field_storage_config', $this->field)->save();
//    entity_create('field_config', $this->instance)->save();
//  }

  function testFieldFormUnlimited() {
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
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
    $this->drupalPostForm(NULL, array(), t('Add another item'));
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$field_name}[1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$field_name}[2][value]", 'No extraneous widget is displayed');
    // TODO : check that non-field inputs are preserved ('title'), etc.

    // Yet another time so that we can play with more values -> 3 widgets.
    $this->drupalPostForm(NULL, array(), t('Add another item'));

    // Prepare values and weights.
    $count = 3;
    $delta_range = $count - 1;
    $values = $weights = $pattern = $expected_values = array();
    $edit = array();
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
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = entity_load('entity_test', $id);
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
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Display creation form -> 1 widget.
    $this->drupalGet('entity_test/add');
    // Check that the Required symbol is present for the multifield label.
    $element = $this->xpath('//h4[contains(@class, "label") and contains(@class, "js-form-required") and contains(text(), :value)]', array(':value' => $this->field['label']));
    $this->assertTrue(isset($element[0]), 'Required symbol added field label.');
    // Check that the label of the field input is visually hidden and contains
    // the field title and an indication of the delta for a11y.
    $element = $this->xpath('//label[@for=:for and contains(@class, "js-form-required") and contains(text(), :value)]', array(':for' => 'edit-field-unlimited-0-value', ':value' => $this->field['label'] . ' (value 1)'));
    $this->assertTrue(isset($element[0]), 'Required symbol not added for field input.');
  }

  /**
   * Tests widget handling of multiple required radios.
   */
  function testFieldFormMultivalueWithRequiredRadio() {
    // Create a multivalue test field.
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Add a required radio field.
    entity_create('field_storage_config', array(
      'field_name' => 'required_radio_test',
      'entity_type' => 'entity_test',
      'type' => 'list_string',
      'settings' => array(
        'allowed_values' => array('yes' => 'yes', 'no' => 'no'),
      ),
    ))->save();
    $field = array(
      'field_name' => 'required_radio_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'required' => TRUE,
    );
    entity_create('field_config', $field)->save();
    entity_get_form_display($field['entity_type'], $field['bundle'], 'default')
      ->setComponent($field['field_name'], array(
        'type' => 'options_buttons',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Press the 'Add more' button.
    $this->drupalPostForm(NULL, array(), t('Add another item'));

    // Verify that no error is thrown by the radio element.
    $this->assertNoFieldByXpath('//div[contains(@class, "error")]', FALSE, 'No error message is displayed.');

    // Verify that the widget is added.
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$field_name}[1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$field_name}[2][value]", 'No extraneous widget is displayed');
  }

  function testFieldFormJSAddMore() {
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Display creation form -> 1 widget.
    $this->drupalGet('entity_test/add');

    // Press 'add more' button a couple times -> 3 widgets.
    // drupalPostAjaxForm() will not work iteratively, so we add those through
    // non-JS submission.
    $this->drupalPostForm(NULL, array(), t('Add another item'));
    $this->drupalPostForm(NULL, array(), t('Add another item'));

    // Prepare values and weights.
    $count = 3;
    $delta_range = $count - 1;
    $values = $weights = $pattern = $expected_values = $edit = array();
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
    // Press 'add more' button through Ajax, and place the expected HTML result
    // as the tested content.
    $commands = $this->drupalPostAjaxForm(NULL, $edit, $field_name . '_add_more');
    $this->setRawContent($commands[2]['data']);

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
  }

  /**
   * Tests widgets handling multiple values.
   */
  function testFieldFormMultipleWidget() {
    // Create a field with fixed cardinality, configure the form to use a
    // "multiple" widget.
    $field_storage = $this->fieldStorageMultiple;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $this->field)->save();
    entity_get_form_display($this->field['entity_type'], $this->field['bundle'], 'default')
      ->setComponent($field_name, array(
        'type' => 'test_field_widget_multiple',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName($field_name, '', 'Widget is displayed.');

    // Create entity with three values.
    $edit = array(
      $field_name => '1, 2, 3',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];

    // Check that the values were saved.
    $entity_init = entity_load('entity_test', $id);
    $this->assertFieldValues($entity_init, $field_name, array(1, 2, 3));

    // Display the form, check that the values are correctly filled in.
    $this->drupalGet('entity_test/manage/' . $id);
    $this->assertFieldByName($field_name, '1, 2, 3', 'Widget is displayed.');

    // Submit the form with more values than the field accepts.
    $edit = array($field_name => '1, 2, 3, 4, 5');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('this field cannot hold more than 4 values', 'Form validation failed.');
    // Check that the field values were not submitted.
    $this->assertFieldValues($entity_init, $field_name, array(1, 2, 3));
  }

  /**
   * Tests fields with no 'edit' access.
   */
  function testFieldFormAccess() {
    $entity_type = 'entity_test_rev';
    // Create a "regular" field.
    $field_storage = $this->fieldStorageSingle;
    $field_storage['entity_type'] = $entity_type;
    $field_name = $field_storage['field_name'];
    $field = $this->field;
    $field['field_name'] = $field_name;
    $field['entity_type'] = $entity_type;
    $field['bundle'] = $entity_type;
    entity_create('field_storage_config', $field_storage)->save();
    entity_create('field_config', $field)->save();
    entity_get_form_display($entity_type, $entity_type, 'default')
      ->setComponent($field_name)
      ->save();

    // Create a field with no edit access. See
    // field_test_entity_field_access().
    $field_storage_no_access = array(
      'field_name' => 'field_no_edit_access',
      'entity_type' => $entity_type,
      'type' => 'test_field',
    );
    $field_name_no_access = $field_storage_no_access['field_name'];
    $field_no_access = array(
      'field_name' => $field_name_no_access,
      'entity_type' => $entity_type,
      'bundle' => $entity_type,
      'default_value' => array(0 => array('value' => 99)),
    );
    entity_create('field_storage_config', $field_storage_no_access)->save();
    entity_create('field_config', $field_no_access)->save();
    entity_get_form_display($field_no_access['entity_type'], $field_no_access['bundle'], 'default')
      ->setComponent($field_name_no_access)
      ->save();

    // Test that the form structure includes full information for each delta
    // apart from #access.
    $entity = entity_create($entity_type, array('id' => 0, 'revision_id' => 0));

    $display = entity_get_form_display($entity_type, $entity_type, 'default');
    $form = array();
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $this->assertFalse($form[$field_name_no_access]['#access'], 'Field #access is FALSE for the field without edit access.');

    // Display creation form.
    $this->drupalGet($entity_type . '/add');
    $this->assertNoFieldByName("{$field_name_no_access}[0][value]", '', 'Widget is not displayed if field access is denied.');

    // Create entity.
    $edit = array(
      "{$field_name}[0][value]" => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match("|$entity_type/manage/(\d+)|", $this->url, $match);
    $id = $match[1];

    // Check that the default value was saved.
    $entity = entity_load($entity_type, $id);
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'Default value was saved for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 1, 'Entered value vas saved for the field with edit access.');

    // Create a new revision.
    $edit = array(
      "{$field_name}[0][value]" => 2,
      'revision' => TRUE,
    );
    $this->drupalPostForm($entity_type . '/manage/' . $id, $edit, t('Save'));

    // Check that the new revision has the expected values.
    $this->container->get('entity.manager')->getStorage($entity_type)->resetCache(array($id));
    $entity = entity_load($entity_type, $id);
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 2, 'New revision has the expected value for the field with edit access.');

    // Check that the revision is also saved in the revisions table.
//    $entity = entity_revision_load($entity_type, $entity->getRevisionId());
    $this->assertEqual($entity->$field_name_no_access->value, 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->$field_name->value, 2, 'New revision has the expected value for the field with edit access.');
  }

  /**
   * Tests hiding a field in a form.
   */
  function testHiddenField() {
    $entity_type = 'entity_test_rev';
    $field_storage = $this->fieldStorageSingle;
    $field_storage['entity_type'] = $entity_type;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;
    $this->field['default_value'] = array(0 => array('value' => 99));
    $this->field['entity_type'] = $entity_type;
    $this->field['bundle'] = $entity_type;
    entity_create('field_storage_config', $field_storage)->save();
    $this->field = entity_create('field_config', $this->field);
    $this->field->save();
    // We explicitly do not assign a widget in a form display, so the field
    // stays hidden in forms.

    // Display the entity creation form.
    $this->drupalGet($entity_type . '/add');

    // Create an entity and test that the default value is assigned correctly to
    // the field that uses the hidden widget.
    $this->assertNoField("{$field_name}[0][value]", 'The field does not appear in the form');
    $this->drupalPostForm(NULL, array(), t('Save'));
    preg_match('|' . $entity_type . '/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test_rev @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = entity_load($entity_type, $id);
    $this->assertEqual($entity->{$field_name}->value, 99, 'Default value was saved');

    // Update the field to remove the default value, and switch to the default
    // widget.
    $this->field->setDefaultValue(array());
    $this->field->save();
    entity_get_form_display($entity_type, $this->field->getTargetBundle(), 'default')
      ->setComponent($this->field->getName(), array(
        'type' => 'test_field_widget',
      ))
      ->save();

    // Display edit form.
    $this->drupalGet($entity_type . '/manage/' . $id);
    $this->assertFieldByName("{$field_name}[0][value]", 99, 'Widget is displayed with the correct default value');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = array("{$field_name}[0][value]" => $value);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test_rev @id has been updated.', array('@id' => $id)), 'Entity was updated');
    \Drupal::entityManager()->getStorage($entity_type)->resetCache(array($id));
    $entity = entity_load($entity_type, $id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'Field value was updated');

    // Set the field back to hidden.
    entity_get_form_display($entity_type, $this->field->getTargetBundle(), 'default')
      ->removeComponent($this->field->getName())
      ->save();

    // Create a new revision.
    $edit = array('revision' => TRUE);
    $this->drupalPostForm($entity_type . '/manage/' . $id, $edit, t('Save'));

    // Check that the expected value has been carried over to the new revision.
    \Drupal::entityManager()->getStorage($entity_type)->resetCache(array($id));
    $entity = entity_load($entity_type, $id);
    $this->assertEqual($entity->{$field_name}->value, $value, 'New revision has the expected value for the field with the Hidden widget');
  }

}
