<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FormTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

class FormTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test', 'options');

  public static function getInfo() {
    return array(
      'name' => 'Field form tests',
      'description' => 'Test Field form handling.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content'));
    $this->drupalLogin($web_user);

    $this->field_single = array('field_name' => 'field_single', 'type' => 'test_field');
    $this->field_multiple = array('field_name' => 'field_multiple', 'type' => 'test_field', 'cardinality' => 4);
    $this->field_unlimited = array('field_name' => 'field_unlimited', 'type' => 'test_field', 'cardinality' => FIELD_CARDINALITY_UNLIMITED);

    $this->instance = array(
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->randomName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => array(
        'test_instance_setting' => $this->randomName(),
      ),
    );
  }

  function testFieldFormSingle() {
    $this->field = $this->field_single;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');

    // Create token value expected for description.
    $token_description = check_plain(config('system.site')->get('name')) . '_description';
    $this->assertText($token_description, 'Token replacement for description is displayed');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget is displayed');
    $this->assertNoField("{$this->field_name}[$langcode][1][value]", 'No extraneous widget is displayed');

    // Check that hook_field_widget_form_alter() does not believe this is the
    // default value form.
    $this->assertNoText('From hook_field_widget_form_alter(): Default form is true.', 'Not default value form in hook_field_widget_form_alter().');

    // Submit with invalid value (field-level validation).
    $edit = array("{$this->field_name}[$langcode][0][value]" => -1);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name does not accept the value -1.', array('%name' => $this->instance['label'])), 'Field validation fails with invalid input.');
    // TODO : check that the correct field is flagged for error.

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], $value, 'Field value was saved');

    // Display edit form.
    $this->drupalGet('test-entity/manage/' . $id . '/edit');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", $value, 'Widget is displayed with the correct default value');
    $this->assertNoField("{$this->field_name}[$langcode][1][value]", 'No extraneous widget is displayed');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('test_entity @id has been updated.', array('@id' => $id)), 'Entity was updated');
    $this->container->get('plugin.manager.entity')->getStorageController('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], $value, 'Field value was updated');

    // Empty the field.
    $value = '';
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost('test-entity/manage/' . $id . '/edit', $edit, t('Save'));
    $this->assertRaw(t('test_entity @id has been updated.', array('@id' => $id)), 'Entity was updated');
    $this->container->get('plugin.manager.entity')->getStorageController('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $this->assertIdentical($entity->{$this->field_name}, array(), 'Field was emptied');
  }

  /**
   * Tests field widget default values on entity forms.
   */
  function testFieldFormDefaultValue() {
    $this->field = $this->field_single;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    $default = rand(1, 127);
    $this->instance['default_value'] = array(array('value' => $default));
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    // Test that the default value is displayed correctly.
    $this->assertFieldByXpath("//input[@name='{$this->field_name}[$langcode][0][value]' and @value='$default']");

    // Try to submit an empty value.
    $edit = array("{$this->field_name}[$langcode][0][value]" => '');
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created.');
    $entity = field_test_entity_test_load($id);
    $this->assertTrue(empty($entity->{$this->field_name}), 'Field is now empty.');
  }

  function testFieldFormSingleRequired() {
    $this->field = $this->field_single;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    $this->instance['required'] = TRUE;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Submit with missing required value.
    $edit = array();
    $this->drupalPost('test-entity/add/test_bundle', $edit, t('Save'));
    $this->assertRaw(t('!name field is required.', array('!name' => $this->instance['label'])), 'Required field with no value fails validation');

    // Create an entity
    $value = mt_rand(1, 127);
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], $value, 'Field value was saved');

    // Edit with missing required value.
    $value = '';
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost('test-entity/manage/' . $id . '/edit', $edit, t('Save'));
    $this->assertRaw(t('!name field is required.', array('!name' => $this->instance['label'])), 'Required field with no value fails validation');
  }

//  function testFieldFormMultiple() {
//    $this->field = $this->field_multiple;
//    $this->field_name = $this->field['field_name'];
//    $this->instance['field_name'] = $this->field_name;
//    field_create_field($this->field);
//    field_create_instance($this->instance);
//  }

  function testFieldFormUnlimited() {
    $this->field = $this->field_unlimited;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form -> 1 widget.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget 1 is displayed');
    $this->assertNoField("{$this->field_name}[$langcode][1][value]", 'No extraneous widget is displayed');

    // Press 'add more' button -> 2 widgets.
    $this->drupalPost(NULL, array(), t('Add another item'));
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$this->field_name}[$langcode][1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$this->field_name}[$langcode][2][value]", 'No extraneous widget is displayed');
    // TODO : check that non-field inpurs are preserved ('title')...

    // Yet another time so that we can play with more values -> 3 widgets.
    $this->drupalPost(NULL, array(), t('Add another item'));

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
      $edit["$this->field_name[$langcode][$delta][value]"] = $value;
      $edit["$this->field_name[$langcode][$delta][_weight]"] = $weight;
      // We'll need three slightly different formats to check the values.
      $values[$delta] = $value;
      $weights[$delta] = $weight;
      $field_values[$weight]['value'] = (string) $value;
      $pattern[$weight] = "<input [^>]*value=\"$value\" [^>]*";
    }

    // Press 'add more' button -> 4 widgets
    $this->drupalPost(NULL, $edit, t('Add another item'));
    for ($delta = 0; $delta <= $delta_range; $delta++) {
      $this->assertFieldByName("$this->field_name[$langcode][$delta][value]", $values[$delta], "Widget $delta is displayed and has the right value");
      $this->assertFieldByName("$this->field_name[$langcode][$delta][_weight]", $weights[$delta], "Widget $delta has the right weight");
    }
    ksort($pattern);
    $pattern = implode('.*', array_values($pattern));
    $this->assertPattern("|$pattern|s", 'Widgets are displayed in the correct order');
    $this->assertFieldByName("$this->field_name[$langcode][$delta][value]", '', "New widget is displayed");
    $this->assertFieldByName("$this->field_name[$langcode][$delta][_weight]", $delta, "New widget has the right weight");
    $this->assertNoField("$this->field_name[$langcode][" . ($delta + 1) . '][value]', 'No extraneous widget is displayed');

    // Submit the form and create the entity.
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = field_test_entity_test_load($id);
    ksort($field_values);
    $field_values = array_values($field_values);
    $this->assertIdentical($entity->{$this->field_name}[$langcode], $field_values, 'Field values were saved in the correct order');

    // Display edit form: check that the expected number of widgets is
    // displayed, with correct values change values, reorder, leave an empty
    // value in the middle.
    // Submit: check that the entity is updated with correct values
    // Re-submit: check that the field can be emptied.

    // Test with several multiple fields in a form
  }

  /**
   * Tests widget handling of multiple required radios.
   */
  function testFieldFormMultivalueWithRequiredRadio() {
    // Create a multivalue test field.
    $this->field = $this->field_unlimited;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Add a required radio field.
    field_create_field(array(
      'field_name' => 'required_radio_test',
      'type' => 'list_text',
      'settings' => array(
        'allowed_values' => array('yes' => 'yes', 'no' => 'no'),
      ),
    ));
    $instance = array(
      'field_name' => 'required_radio_test',
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'required' => TRUE,
    );
    field_create_instance($instance);
    entity_get_form_display($instance['entity_type'], $instance['bundle'], 'default')
      ->setComponent($instance['field_name'], array(
        'type' => 'options_buttons',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');

    // Press the 'Add more' button.
    $this->drupalPost(NULL, array(), t('Add another item'));

    // Verify that no error is thrown by the radio element.
    $this->assertNoFieldByXpath('//div[contains(@class, "error")]', FALSE, 'No error message is displayed.');

    // Verify that the widget is added.
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget 1 is displayed');
    $this->assertFieldByName("{$this->field_name}[$langcode][1][value]", '', 'New widget is displayed');
    $this->assertNoField("{$this->field_name}[$langcode][2][value]", 'No extraneous widget is displayed');
  }

  function testFieldFormJSAddMore() {
    $this->field = $this->field_unlimited;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name)
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form -> 1 widget.
    $this->drupalGet('test-entity/add/test_bundle');

    // Press 'add more' button a couple times -> 3 widgets.
    // drupalPostAJAX() will not work iteratively, so we add those through
    // non-JS submission.
    $this->drupalPost(NULL, array(), t('Add another item'));
    $this->drupalPost(NULL, array(), t('Add another item'));

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
      $edit["$this->field_name[$langcode][$delta][value]"] = $value;
      $edit["$this->field_name[$langcode][$delta][_weight]"] = $weight;
      // We'll need three slightly different formats to check the values.
      $values[$delta] = $value;
      $weights[$delta] = $weight;
      $field_values[$weight]['value'] = (string) $value;
      $pattern[$weight] = "<input [^>]*value=\"$value\" [^>]*";
    }
    // Press 'add more' button through Ajax, and place the expected HTML result
    // as the tested content.
    $commands = $this->drupalPostAJAX(NULL, $edit, $this->field_name . '_add_more');
    $this->content = $commands[1]['data'];

    for ($delta = 0; $delta <= $delta_range; $delta++) {
      $this->assertFieldByName("$this->field_name[$langcode][$delta][value]", $values[$delta], "Widget $delta is displayed and has the right value");
      $this->assertFieldByName("$this->field_name[$langcode][$delta][_weight]", $weights[$delta], "Widget $delta has the right weight");
    }
    ksort($pattern);
    $pattern = implode('.*', array_values($pattern));
    $this->assertPattern("|$pattern|s", 'Widgets are displayed in the correct order');
    $this->assertFieldByName("$this->field_name[$langcode][$delta][value]", '', "New widget is displayed");
    $this->assertFieldByName("$this->field_name[$langcode][$delta][_weight]", $delta, "New widget has the right weight");
    $this->assertNoField("$this->field_name[$langcode][" . ($delta + 1) . '][value]', 'No extraneous widget is displayed');
  }

  /**
   * Tests widgets handling multiple values.
   */
  function testFieldFormMultipleWidget() {
    // Create a field with fixed cardinality and an instance using a multiple
    // widget.
    $this->field = $this->field_multiple;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->field_name, array(
        'type' => 'test_field_widget_multiple',
      ))
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode]", '', 'Widget is displayed.');

    // Create entity with three values.
    $edit = array("{$this->field_name}[$langcode]" => '1, 2, 3');
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];

    // Check that the values were saved.
    $entity_init = field_test_create_entity($id);
    $this->assertFieldValues($entity_init, $this->field_name, $langcode, array(1, 2, 3));

    // Display the form, check that the values are correctly filled in.
    $this->drupalGet('test-entity/manage/' . $id . '/edit');
    $this->assertFieldByName("{$this->field_name}[$langcode]", '1, 2, 3', 'Widget is displayed.');

    // Submit the form with more values than the field accepts.
    $edit = array("{$this->field_name}[$langcode]" => '1, 2, 3, 4, 5');
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw('this field cannot hold more than 4 values', 'Form validation failed.');
    // Check that the field values were not submitted.
    $this->assertFieldValues($entity_init, $this->field_name, $langcode, array(1, 2, 3));
  }

  /**
   * Tests fields with no 'edit' access.
   */
  function testFieldFormAccess() {
    // Create a "regular" field.
    $field = $this->field_single;
    $field_name = $field['field_name'];
    $instance = $this->instance;
    $instance['field_name'] = $field_name;
    field_create_field($field);
    field_create_instance($instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($field_name)
      ->save();

    // Create a field with no edit access - see field_test_field_access().
    $field_no_access = array(
      'field_name' => 'field_no_edit_access',
      'type' => 'test_field',
    );
    $field_name_no_access = $field_no_access['field_name'];
    $instance_no_access = array(
      'field_name' => $field_name_no_access,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'default_value' => array(0 => array('value' => 99)),
    );
    field_create_field($field_no_access);
    field_create_instance($instance_no_access);
    entity_get_form_display($instance_no_access['entity_type'], $instance_no_access['bundle'], 'default')
      ->setComponent($field_name_no_access)
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Test that the form structure includes full information for each delta
    // apart from #access.
    $entity_type = 'test_entity';
    $entity = field_test_create_entity(0, 0, $this->instance['bundle']);

    $form = array();
    $form_state = form_state_defaults();
    $form_state['form_display'] = entity_get_form_display($entity_type, $this->instance['bundle'], 'default');
    field_attach_form($entity, $form, $form_state);

    $this->assertEqual($form[$field_name_no_access][$langcode][0]['value']['#entity_type'], $entity_type, 'The correct entity type is set in the field structure.');
    $this->assertFalse($form[$field_name_no_access]['#access'], 'Field #access is FALSE for the field without edit access.');

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertNoFieldByName("{$field_name_no_access}[$langcode][0][value]", '', 'Widget is not displayed if field access is denied.');

    // Create entity.
    $edit = array("{$field_name}[$langcode][0][value]" => 1);
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];

    // Check that the default value was saved.
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$field_name_no_access}[$langcode][0]['value'], 99, 'Default value was saved for the field with no edit access.');
    $this->assertEqual($entity->{$field_name}[$langcode][0]['value'], 1, 'Entered value vas saved for the field with edit access.');

    // Create a new revision.
    $edit = array("{$field_name}[$langcode][0][value]" => 2, 'revision' => TRUE);
    $this->drupalPost('test-entity/manage/' . $id . '/edit', $edit, t('Save'));

    // Check that the new revision has the expected values.
    $this->container->get('plugin.manager.entity')->getStorageController('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$field_name_no_access}[$langcode][0]['value'], 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->{$field_name}[$langcode][0]['value'], 2, 'New revision has the expected value for the field with edit access.');

    // Check that the revision is also saved in the revisions table.
    $entity = field_test_entity_test_load($id, $entity->ftvid);
    $this->assertEqual($entity->{$field_name_no_access}[$langcode][0]['value'], 99, 'New revision has the expected value for the field with no edit access.');
    $this->assertEqual($entity->{$field_name}[$langcode][0]['value'], 2, 'New revision has the expected value for the field with edit access.');
  }

  /**
   * Tests Field API form integration within a subform.
   */
  function testNestedFieldForm() {
    // Add two instances on the 'test_bundle'
    field_create_field($this->field_single);
    field_create_field($this->field_unlimited);
    $this->instance['field_name'] = 'field_single';
    $this->instance['label'] = 'Single field';
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->instance['field_name'])
      ->save();
    $this->instance['field_name'] = 'field_unlimited';
    $this->instance['label'] = 'Unlimited field';
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->instance['field_name'])
      ->save();

    // Create two entities.
    $entity_1 = field_test_create_entity(1, 1);
    $entity_1->is_new = TRUE;
    $entity_1->field_single[Language::LANGCODE_NOT_SPECIFIED][] = array('value' => 0);
    $entity_1->field_unlimited[Language::LANGCODE_NOT_SPECIFIED][] = array('value' => 1);
    field_test_entity_save($entity_1);

    $entity_2 = field_test_create_entity(2, 2);
    $entity_2->is_new = TRUE;
    $entity_2->field_single[Language::LANGCODE_NOT_SPECIFIED][] = array('value' => 10);
    $entity_2->field_unlimited[Language::LANGCODE_NOT_SPECIFIED][] = array('value' => 11);
    field_test_entity_save($entity_2);

    // Display the 'combined form'.
    $this->drupalGet('test-entity/nested/1/2');
    $this->assertFieldByName('field_single[und][0][value]', 0, 'Entity 1: field_single value appears correctly is the form.');
    $this->assertFieldByName('field_unlimited[und][0][value]', 1, 'Entity 1: field_unlimited value 0 appears correctly is the form.');
    $this->assertFieldByName('entity_2[field_single][und][0][value]', 10, 'Entity 2: field_single value appears correctly is the form.');
    $this->assertFieldByName('entity_2[field_unlimited][und][0][value]', 11, 'Entity 2: field_unlimited value 0 appears correctly is the form.');

    // Submit the form and check that the entities are updated accordingly.
    $edit = array(
      'field_single[und][0][value]' => 1,
      'field_unlimited[und][0][value]' => 2,
      'field_unlimited[und][1][value]' => 3,
      'entity_2[field_single][und][0][value]' => 11,
      'entity_2[field_unlimited][und][0][value]' => 12,
      'entity_2[field_unlimited][und][1][value]' => 13,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    field_cache_clear();
    $entity_1 = field_test_create_entity(1);
    $entity_2 = field_test_create_entity(2);
    $this->assertFieldValues($entity_1, 'field_single', Language::LANGCODE_NOT_SPECIFIED, array(1));
    $this->assertFieldValues($entity_1, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(2, 3));
    $this->assertFieldValues($entity_2, 'field_single', Language::LANGCODE_NOT_SPECIFIED, array(11));
    $this->assertFieldValues($entity_2, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(12, 13));

    // Submit invalid values and check that errors are reported on the
    // correct widgets.
    $edit = array(
      'field_unlimited[und][1][value]' => -1,
    );
    $this->drupalPost('test-entity/nested/1/2', $edit, t('Save'));
    $this->assertRaw(t('%label does not accept the value -1', array('%label' => 'Unlimited field')), 'Entity 1: the field validation error was reported.');
    $error_field = $this->xpath('//input[@id=:id and contains(@class, "error")]', array(':id' => 'edit-field-unlimited-und-1-value'));
    $this->assertTrue($error_field, 'Entity 1: the error was flagged on the correct element.');
    $edit = array(
      'entity_2[field_unlimited][und][1][value]' => -1,
    );
    $this->drupalPost('test-entity/nested/1/2', $edit, t('Save'));
    $this->assertRaw(t('%label does not accept the value -1', array('%label' => 'Unlimited field')), 'Entity 2: the field validation error was reported.');
    $error_field = $this->xpath('//input[@id=:id and contains(@class, "error")]', array(':id' => 'edit-entity-2-field-unlimited-und-1-value'));
    $this->assertTrue($error_field, 'Entity 2: the error was flagged on the correct element.');

    // Test that reordering works on both entities.
    $edit = array(
      'field_unlimited[und][0][_weight]' => 0,
      'field_unlimited[und][1][_weight]' => -1,
      'entity_2[field_unlimited][und][0][_weight]' => 0,
      'entity_2[field_unlimited][und][1][_weight]' => -1,
    );
    $this->drupalPost('test-entity/nested/1/2', $edit, t('Save'));
    field_cache_clear();
    $this->assertFieldValues($entity_1, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(3, 2));
    $this->assertFieldValues($entity_2, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(13, 12));

    // Test the 'add more' buttons. Only Ajax submission is tested, because
    // the two 'add more' buttons present in the form have the same #value,
    // which confuses drupalPost().
    // 'Add more' button in the first entity:
    $this->drupalGet('test-entity/nested/1/2');
    $this->drupalPostAJAX(NULL, array(), 'field_unlimited_add_more');
    $this->assertFieldByName('field_unlimited[und][0][value]', 3, 'Entity 1: field_unlimited value 0 appears correctly is the form.');
    $this->assertFieldByName('field_unlimited[und][1][value]', 2, 'Entity 1: field_unlimited value 1 appears correctly is the form.');
    $this->assertFieldByName('field_unlimited[und][2][value]', '', 'Entity 1: field_unlimited value 2 appears correctly is the form.');
    $this->assertFieldByName('field_unlimited[und][3][value]', '', 'Entity 1: an empty widget was added for field_unlimited value 3.');
    // 'Add more' button in the first entity (changing field values):
    $edit = array(
      'entity_2[field_unlimited][und][0][value]' => 13,
      'entity_2[field_unlimited][und][1][value]' => 14,
      'entity_2[field_unlimited][und][2][value]' => 15,
    );
    $this->drupalPostAJAX(NULL, $edit, 'entity_2_field_unlimited_add_more');
    $this->assertFieldByName('entity_2[field_unlimited][und][0][value]', 13, 'Entity 2: field_unlimited value 0 appears correctly is the form.');
    $this->assertFieldByName('entity_2[field_unlimited][und][1][value]', 14, 'Entity 2: field_unlimited value 1 appears correctly is the form.');
    $this->assertFieldByName('entity_2[field_unlimited][und][2][value]', 15, 'Entity 2: field_unlimited value 2 appears correctly is the form.');
    $this->assertFieldByName('entity_2[field_unlimited][und][3][value]', '', 'Entity 2: an empty widget was added for field_unlimited value 3.');
    // Save the form and check values are saved correclty.
    $this->drupalPost(NULL, array(), t('Save'));
    field_cache_clear();
    $this->assertFieldValues($entity_1, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(3, 2));
    $this->assertFieldValues($entity_2, 'field_unlimited', Language::LANGCODE_NOT_SPECIFIED, array(13, 14, 15));
  }

  /**
   * Tests the Hidden widget.
   */
  function testFieldFormHiddenWidget() {
    $this->field = $this->field_single;
    $this->field_name = $this->field['field_name'];
    $this->instance['field_name'] = $this->field_name;
    $this->instance['default_value'] = array(0 => array('value' => 99));
    field_create_field($this->field);
    field_create_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->instance['field_name'], array(
        'type' => 'hidden',
      ))
      ->save();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display the entity creation form.
    $this->drupalGet('test-entity/add/test_bundle');

    // Create an entity and test that the default value is assigned correctly to
    // the field that uses the hidden widget.
    $this->assertNoField("{$this->field_name}[$langcode][0][value]", 'The hidden widget is not displayed');
    $this->drupalPost(NULL, array(), t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], 99, 'Default value was saved');

    // Update the instance to remove the default value and switch to the
    // default widget.
    $this->instance['default_value'] = NULL;
    field_update_instance($this->instance);
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->instance['field_name'], array(
        'type' => 'test_field_widget',
      ))
      ->save();

    // Display edit form.
    $this->drupalGet('test-entity/manage/' . $id . '/edit');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", 99, 'Widget is displayed with the correct default value');

    // Update the entity.
    $value = mt_rand(1, 127);
    $edit = array("{$this->field_name}[$langcode][0][value]" => $value);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('test_entity @id has been updated.', array('@id' => $id)), 'Entity was updated');
    entity_get_controller('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], $value, 'Field value was updated');

    // Update the form display and switch to the Hidden widget again.
    entity_get_form_display($this->instance['entity_type'], $this->instance['bundle'], 'default')
      ->setComponent($this->instance['field_name'], array(
        'type' => 'hidden',
      ))
      ->save();

    // Create a new revision.
    $edit = array('revision' => TRUE);
    $this->drupalPost('test-entity/manage/' . $id . '/edit', $edit, t('Save'));

    // Check that the expected value has been carried over to the new revision.
    entity_get_controller('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $this->assertEqual($entity->{$this->field_name}[$langcode][0]['value'], $value, 'New revision has the expected value for the field with the Hidden widget');
  }
}
