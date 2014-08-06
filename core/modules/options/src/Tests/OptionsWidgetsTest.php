<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsWidgetsTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Tests the Options widgets.
 *
 * @group options
 */
class OptionsWidgetsTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'options', 'entity_test', 'options_test', 'taxonomy', 'field_ui');

  /**
   * A field storage with cardinality 1 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card_1;

  /**
   * A field storage with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card_2;

  /**
   * A user with permission to view and manage entities.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $web_user;


  function setUp() {
    parent::setUp();

    // Field storage with cardinality 1.
    $this->card_1 = entity_create('field_storage_config', array(
      'name' => 'card_1',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
          // Make sure that HTML entities in option text are not double-encoded.
          3 => 'Some HTML encoded markup with &lt; &amp; &gt;',
        ),
      ),
    ));
    $this->card_1->save();

    // Field storage with cardinality 2.
    $this->card_2 = entity_create('field_storage_config', array(
      'name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => array(
        'allowed_values' => array(
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ),
      ),
    ));
    $this->card_2->save();

    // Create a web user.
    $this->web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests the 'options_buttons' widget (single select).
   */
  function testRadioButtons() {
    // Create an instance of the 'single value' field.
    $instance = entity_create('field_instance_config', array(
      'field_storage' => $this->card_1,
      'bundle' => 'entity_test',
    ));
    $instance->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card_1->getName(), array(
        'type' => 'options_buttons',
      ))
      ->save();

    // Create an entity.
    $entity = entity_create('entity_test', array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // With no field data, no buttons are checked.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertNoFieldChecked('edit-card-1-0');
    $this->assertNoFieldChecked('edit-card-1-1');
    $this->assertNoFieldChecked('edit-card-1-2');
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>', 'Option text was properly filtered.');
    $this->assertRaw('Some HTML encoded markup with &lt; &amp; &gt;');

    // Select first option.
    $edit = array('card_1' => 0);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array(0));

    // Check that the selected button is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFieldChecked('edit-card-1-0');
    $this->assertNoFieldChecked('edit-card-1-1');
    $this->assertNoFieldChecked('edit-card-1-2');

    // Unselect option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array());

    // Check that required radios with one option is auto-selected.
    $this->card_1->settings['allowed_values'] = array(99 => 'Only allowed value');
    $this->card_1->save();
    $instance->required = TRUE;
    $instance->save();
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFieldChecked('edit-card-1-99');
  }

  /**
   * Tests the 'options_buttons' widget (multiple select).
   */
  function testCheckBoxes() {
    // Create an instance of the 'multiple values' field.
    $instance = entity_create('field_instance_config', array(
      'field_storage' => $this->card_2,
      'bundle' => 'entity_test',
    ));
    $instance->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card_2->getName(), array(
        'type' => 'options_buttons',
      ))
      ->save();

    // Create an entity.
    $entity = entity_create('entity_test', array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is checked.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertNoFieldChecked('edit-card-2-0');
    $this->assertNoFieldChecked('edit-card-2-1');
    $this->assertNoFieldChecked('edit-card-2-2');
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>', 'Option text was properly filtered.');

    // Submit form: select first and third options.
    $edit = array(
      'card_2[0]' => TRUE,
      'card_2[1]' => FALSE,
      'card_2[2]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0, 2));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFieldChecked('edit-card-2-0');
    $this->assertNoFieldChecked('edit-card-2-1');
    $this->assertFieldChecked('edit-card-2-2');

    // Submit form: select only first option.
    $edit = array(
      'card_2[0]' => TRUE,
      'card_2[1]' => FALSE,
      'card_2[2]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFieldChecked('edit-card-2-0');
    $this->assertNoFieldChecked('edit-card-2-1');
    $this->assertNoFieldChecked('edit-card-2-2');

    // Submit form: select the three options while the field accepts only 2.
    $edit = array(
      'card_2[0]' => TRUE,
      'card_2[1]' => TRUE,
      'card_2[2]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('this field cannot hold more than 2 values', 'Validation error was displayed.');

    // Submit form: uncheck all options.
    $edit = array(
      'card_2[0]' => FALSE,
      'card_2[1]' => FALSE,
      'card_2[2]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check that the value was saved.
    $this->assertFieldValues($entity_init, 'card_2', array());

    // Required checkbox with one option is auto-selected.
    $this->card_2->settings['allowed_values'] = array(99 => 'Only allowed value');
    $this->card_2->save();
    $instance->required = TRUE;
    $instance->save();
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFieldChecked('edit-card-2-99');
  }

  /**
   * Tests the 'options_select' widget (single select).
   */
  function testSelectListSingle() {
    // Create an instance of the 'single value' field.
    $instance = entity_create('field_instance_config', array(
      'field_storage' => $this->card_1,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ));
    $instance->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card_1->getName(), array(
        'type' => 'options_select',
      ))
      ->save();

    // Create an entity.
    $entity = entity_create('entity_test', array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    // A required field without any value has a "none" option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1', ':label' => t('- Select a value -'))), 'A required select list has a "Select a value" choice.');

    // With no field data, nothing is selected.
    $this->assertNoOptionSelected('edit-card-1', '_none');
    $this->assertNoOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');

    // Submit form: select invalid 'none' option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('!title field is required.', array('!title' => $instance->getName())), 'Cannot save a required field when selecting "none" from the select list.');

    // Submit form: select first option.
    $edit = array('card_1' => 0);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    // A required field with a value has no 'none' option.
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value="_none"]', array(':id' => 'edit-card-1')), 'A required select list with an actual value has no "none" choice.');
    $this->assertOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);

    // Make the field non required.
    $instance->required = FALSE;
    $instance->save();

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    // A non-required field has a 'none' option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1', ':label' => t('- None -'))), 'A non-required select list has a "None" choice.');
    // Submit form: Unselect the option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm('entity_test/manage/' . $entity->id(), $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array());

    // Test optgroups.

    $this->card_1->settings['allowed_values'] = array();
    $this->card_1->settings['allowed_values_function'] = 'options_test_allowed_values_callback';
    $this->card_1->save();

    // Display form: with no field data, nothing is selected
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertNoOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');
    $this->assertRaw('More &lt;script&gt;dangerous&lt;/script&gt; markup', 'Option group text was properly filtered.');
    $this->assertRaw('Group 1', 'Option groups are displayed.');

    // Submit form: select first option.
    $edit = array('card_1' => 0);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);

    // Submit form: Unselect the option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm('entity_test/manage/' . $entity->id(), $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array());
  }

  /**
   * Tests the 'options_select' widget (multiple select).
   */
  function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $instance = entity_create('field_instance_config', array(
      'field_storage' => $this->card_2,
      'bundle' => 'entity_test',
    ));
    $instance->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->card_2->getName(), array(
        'type' => 'options_select',
      ))
      ->save();

    // Create an entity.
    $entity = entity_create('entity_test', array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertOptionSelected("edit-card-2", '_none');
    $this->assertNoOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');

    // Submit form: select first and third options.
    $edit = array('card_2[]' => array(0 => 0, 2 => 2));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0, 2));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertOptionSelected('edit-card-2', 2);

    // Submit form: select only first option.
    $edit = array('card_2[]' => array(0 => 0));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);

    // Submit form: select the three options while the field accepts only 2.
    $edit = array('card_2[]' => array(0 => 0, 1 => 1, 2 => 2));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('this field cannot hold more than 2 values', 'Validation error was displayed.');

    // Submit form: uncheck all options.
    $edit = array('card_2[]' => array());
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array());

    // Test the 'None' option.

    // Check that the 'none' option has no effect if actual options are selected
    // as well.
    $edit = array('card_2[]' => array('_none' => '_none', 0 => 0));
    $this->drupalPostForm('entity_test/manage/' . $entity->id(), $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Check that selecting the 'none' option empties the field.
    $edit = array('card_2[]' => array('_none' => '_none'));
    $this->drupalPostForm('entity_test/manage/' . $entity->id(), $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array());

    // A required select list does not have an empty key.
    $instance->required = TRUE;
    $instance->save();
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value=""]', array(':id' => 'edit-card-2')), 'A required select list does not have an empty key.');

    // We do not have to test that a required select list with one option is
    // auto-selected because the browser does it for us.

    // Test optgroups.

    // Use a callback function defining optgroups.
    $this->card_2->settings['allowed_values'] = array();
    $this->card_2->settings['allowed_values_function'] = 'options_test_allowed_values_callback';
    $this->card_2->save();
    $instance->required = FALSE;
    $instance->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertNoOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');
    $this->assertRaw('More &lt;script&gt;dangerous&lt;/script&gt; markup', 'Option group text was properly filtered.');
    $this->assertRaw('Group 1', 'Option groups are displayed.');

    // Submit form: select first option.
    $edit = array('card_2[]' => array(0 => 0));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id());
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);

    // Submit form: Unselect the option.
    $edit = array('card_2[]' => array('_none' => '_none'));
    $this->drupalPostForm('entity_test/manage/' . $entity->id(), $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array());
  }

}
