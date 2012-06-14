<?php

/**
 * @file
 * Definition of Drupal\field_ui\Tests\ManageFieldsTest.
 */

namespace Drupal\field_ui\Tests;

/**
 * Tests the functionality of the 'Manage fields' screen.
 */
class ManageFieldsTest extends FieldUiTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Manage fields',
      'description' => 'Test the Field UI "Manage fields" screen.',
      'group' => 'Field UI',
    );
  }

  function setUp() {
    parent::setUp();

    // Create random field name.
    $this->field_label = $this->randomName(8);
    $this->field_name_input =  strtolower($this->randomName(8));
    $this->field_name = 'field_'. $this->field_name_input;

    // Create Basic page and Article node types.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Create a vocabulary named "Tags".
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Tags',
      'machine_name' => 'tags',
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));
    taxonomy_vocabulary_save($vocabulary);

    $field = array(
      'field_name' => 'field_' . $vocabulary->machine_name,
      'type' => 'taxonomy_term_reference',
    );
    field_create_field($field);

    $instance = array(
      'field_name' => 'field_' . $vocabulary->machine_name,
      'entity_type' => 'node',
      'label' => 'Tags',
      'bundle' => 'article',
    );
    field_create_instance($instance);
  }

  /**
   * Runs the field CRUD tests.
   *
   * In order to act on the same fields, and not create the fields over and over
   * again the following tests create, update and delete the same fields.
   */
  function testCRUDFields() {
    $this->manageFieldsPage();
    $this->createField();
    $this->updateField();
    $this->addExistingField();
  }

  /**
   * Tests the manage fields page.
   */
  function manageFieldsPage() {
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/fields');
    // Check all table columns.
    $table_headers = array(
      t('Label'),
      t('Machine name'),
      t('Field type'),
      t('Widget'),
      t('Operations'),
    );
    foreach ($table_headers as $table_header) {
      // We check that the label appear in the table headings.
      $this->assertRaw($table_header . '</th>', t('%table_header table header was found.', array('%table_header' => $table_header)));
    }

    // "Add new field" and "Add existing field" aren't a table heading so just
    // test the text.
    foreach (array('Add new field', 'Add existing field') as $element) {
      $this->assertText($element, t('"@element" was found.', array('@element' => $element)));
    }
  }

  /**
   * Tests adding a new field.
   *
   * @todo Assert properties can bet set in the form and read back in $field and
   * $instances.
   */
  function createField() {
    // Create a test field.
    $edit = array(
      'fields[_add_new_field][label]' => $this->field_label,
      'fields[_add_new_field][field_name]' => $this->field_name_input,
    );
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, $edit);

    // Assert the field appears in the "add existing field" section for
    // different entity types; e.g. if a field was added in a node entity, it
    // should also appear in the 'taxonomy term' entity.
    $vocabulary = taxonomy_vocabulary_load(1);
    $this->drupalGet('admin/structure/taxonomy/' . $vocabulary->machine_name . '/fields');
    $this->assertTrue($this->xpath('//select[@name="fields[_add_existing_field][field_name]"]//option[@value="' . $this->field_name . '"]'), t('Existing field was found in account settings.'));
  }

  /**
   * Tests editing an existing field.
   */
  function updateField() {
    // Go to the field edit page.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/fields/' . $this->field_name);

    // Populate the field settings with new settings.
    $string = 'updated dummy test string';
    $edit = array(
      'field[settings][test_field_setting]' => $string,
      'instance[settings][test_instance_setting]' => $string,
      'instance[widget][settings][test_widget_setting]' => $string,
    );
    $this->drupalPost(NULL, $edit, t('Save settings'));

    // Assert the field settings are correct.
    $this->assertFieldSettings($this->type, $this->field_name, $string);

    // Assert redirection back to the "manage fields" page.
    $this->assertText(t('Saved @label configuration.', array('@label' => $this->field_label)), t('Redirected to "Manage fields" page.'));
  }

  /**
   * Tests adding an existing field in another content type.
   */
  function addExistingField() {
    // Check "Add existing field" appears.
    $this->drupalGet('admin/structure/types/manage/page/fields');
    $this->assertRaw(t('Add existing field'), t('"Add existing field" was found.'));

    // Check that the list of options respects entity type restrictions on
    // fields. The 'comment' field is restricted to the 'comment' entity type
    // and should not appear in the list.
    $this->assertFalse($this->xpath('//select[@id="edit-add-existing-field-field-name"]//option[@value="comment"]'), t('The list of options respects entity type restrictions.'));

    // Add a new field based on an existing field.
    $edit = array(
      'fields[_add_existing_field][label]' => $this->field_label . '_2',
      'fields[_add_existing_field][field_name]' => $this->field_name,
    );
    $this->fieldUIAddExistingField("admin/structure/types/manage/page", $edit);
  }

  /**
   * Asserts field settings are as expected.
   *
   * @param $bundle
   *   The bundle name for the instance.
   * @param $field_name
   *   The field name for the instance.
   * @param $string
   *   The settings text.
   * @param $entity_type
   *   The entity type for the instance.
   */
  function assertFieldSettings($bundle, $field_name, $string = 'dummy test string', $entity_type = 'node') {
    // Reset the fields info.
    _field_info_collate_fields_reset();
    // Assert field settings.
    $field = field_info_field($field_name);
    $this->assertTrue($field['settings']['test_field_setting'] == $string, t('Field settings were found.'));

    // Assert instance and widget settings.
    $instance = field_info_instance($entity_type, $field_name, $bundle);
    $this->assertTrue($instance['settings']['test_instance_setting'] == $string, t('Field instance settings were found.'));
    $this->assertTrue($instance['widget']['settings']['test_widget_setting'] == $string, t('Field widget settings were found.'));
  }

  /**
   * Tests that default value is correctly validated and saved.
   */
  function testDefaultValue() {
    // Create a test field and instance.
    $field_name = 'test';
    $field = array(
      'field_name' => $field_name,
      'type' => 'test_field'
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $this->type,
    );
    field_create_instance($instance);

    $langcode = LANGUAGE_NOT_SPECIFIED;
    $admin_path = 'admin/structure/types/manage/' . $this->type . '/fields/' . $field_name;
    $element_id = "edit-$field_name-$langcode-0-value";
    $element_name = "{$field_name}[$langcode][0][value]";
    $this->drupalGet($admin_path);
    $this->assertFieldById($element_id, '', t('The default value widget was empty.'));

    // Check that invalid default values are rejected.
    $edit = array($element_name => '-1');
    $this->drupalPost($admin_path, $edit, t('Save settings'));
    $this->assertText("$field_name does not accept the value -1", t('Form vaildation failed.'));

    // Check that the default value is saved.
    $edit = array($element_name => '1');
    $this->drupalPost($admin_path, $edit, t('Save settings'));
    $this->assertText("Saved $field_name configuration", t('The form was successfully submitted.'));
    $instance = field_info_instance('node', $field_name, $this->type);
    $this->assertEqual($instance['default_value'], array(array('value' => 1)), t('The default value was correctly saved.'));

    // Check that the default value shows up in the form
    $this->drupalGet($admin_path);
    $this->assertFieldById($element_id, '1', t('The default value widget was displayed with the correct value.'));

    // Check that the default value can be emptied.
    $edit = array($element_name => '');
    $this->drupalPost(NULL, $edit, t('Save settings'));
    $this->assertText("Saved $field_name configuration", t('The form was successfully submitted.'));
    field_info_cache_clear();
    $instance = field_info_instance('node', $field_name, $this->type);
    $this->assertEqual($instance['default_value'], NULL, t('The default value was correctly saved.'));
  }

  /**
   * Tests that deletion removes fields and instances as expected.
   */
  function testDeleteField() {
    // Create a new field.
    $bundle_path1 = 'admin/structure/types/manage/' . $this->type;
    $edit1 = array(
      'fields[_add_new_field][label]' => $this->field_label,
      'fields[_add_new_field][field_name]' => $this->field_name_input,
    );
    $this->fieldUIAddNewField($bundle_path1, $edit1);

    // Create an additional node type.
    $type_name2 = strtolower($this->randomName(8)) . '_test';
    $type2 = $this->drupalCreateContentType(array('name' => $type_name2, 'type' => $type_name2));
    $type_name2 = $type2->type;

    // Add an instance to the second node type.
    $bundle_path2 = 'admin/structure/types/manage/' . $type_name2;
    $edit2 = array(
      'fields[_add_existing_field][label]' => $this->field_label,
      'fields[_add_existing_field][field_name]' => $this->field_name,
    );
    $this->fieldUIAddExistingField($bundle_path2, $edit2);

    // Delete the first instance.
    $this->fieldUIDeleteField($bundle_path1, $this->field_name, $this->field_label, $this->type);

    // Reset the fields info.
    _field_info_collate_fields_reset();
    // Check that the field instance was deleted.
    $this->assertNull(field_info_instance('node', $this->field_name, $this->type), t('Field instance was deleted.'));
    // Check that the field was not deleted
    $this->assertNotNull(field_info_field($this->field_name), t('Field was not deleted.'));

    // Delete the second instance.
    $this->fieldUIDeleteField($bundle_path2, $this->field_name, $this->field_label, $type_name2);

    // Reset the fields info.
    _field_info_collate_fields_reset();
    // Check that the field instance was deleted.
    $this->assertNull(field_info_instance('node', $this->field_name, $type_name2), t('Field instance was deleted.'));
    // Check that the field was deleted too.
    $this->assertNull(field_info_field($this->field_name), t('Field was deleted.'));
  }

  /**
   * Tests that Field UI respects the 'no_ui' option in hook_field_info().
   */
  function testHiddenFields() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type . '/fields/';

    // Check that the field type is not available in the 'add new field' row.
    $this->drupalGet($bundle_path);
    $this->assertFalse($this->xpath('//select[@id="edit-add-new-field-type"]//option[@value="hidden_test_field"]'), t("The 'add new field' select respects field types 'no_ui' property."));

    // Create a field and an instance programmatically.
    $field_name = 'hidden_test_field';
    field_create_field(array('field_name' => $field_name, 'type' => $field_name));
    $instance = array(
      'field_name' => $field_name,
      'bundle' => $this->type,
      'entity_type' => 'node',
      'label' => t('Hidden field'),
      'widget' => array('type' => 'test_field_widget'),
    );
    field_create_instance($instance);
    $this->assertTrue(field_read_instance('node', $field_name, $this->type), t('An instance of the field %field was created programmatically.', array('%field' => $field_name)));

    // Check that the newly added instance appears on the 'Manage Fields'
    // screen.
    $this->drupalGet($bundle_path);
    $this->assertFieldByXPath('//table[@id="field-overview"]//td[1]', $instance['label'], t('Field was created and appears in the overview page.'));

    // Check that the instance does not appear in the 'add existing field' row
    // on other bundles.
    $bundle_path = 'admin/structure/types/manage/article/fields/';
    $this->drupalGet($bundle_path);
    $this->assertFalse($this->xpath('//select[@id="edit-add-existing-field-field-name"]//option[@value=:field_name]', array(':field_name' => $field_name)), t("The 'add existing field' select respects field types 'no_ui' property."));
  }

  /**
   * Tests renaming a bundle.
   */
  function testRenameBundle() {
    $type2 = strtolower($this->randomName(8)) . '_test';

    $options = array(
      'type' => $type2,
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type, $options, t('Save content type'));

    $this->drupalGet('admin/structure/types/manage/' . $type2 . '/fields');
  }

  /**
   * Tests that a duplicate field name is caught by validation.
   */
  function testDuplicateFieldName() {
    // field_tags already exists, so we're expecting an error when trying to
    // create a new field with the same name.
    $edit = array(
      'fields[_add_new_field][field_name]' => 'tags',
      'fields[_add_new_field][label]' => $this->randomName(),
      'fields[_add_new_field][type]' => 'taxonomy_term_reference',
      'fields[_add_new_field][widget_type]' => 'options_select',
    );
    $url = 'admin/structure/types/manage/' . $this->type . '/fields';
    $this->drupalPost($url, $edit, t('Save'));

    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));
    $this->assertUrl($url, array(), 'Stayed on the same page.');
  }
}
