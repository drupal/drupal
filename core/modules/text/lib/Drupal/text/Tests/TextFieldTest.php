<?php

/**
 * @file
 * Definition of Drupal\text\TextFieldTest.
 */

namespace Drupal\text\Tests;

use Drupal\Core\Language\Language;
use Drupal\field\FieldValidationException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of text fields.
 */
class TextFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  protected $instance;
  protected $admin_user;
  protected $web_user;

  public static function getInfo() {
    return array(
      'name'  => 'Text field',
      'description'  => "Test the creation of text fields.",
      'group' => 'Field types'
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer filters'));
    $this->web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content'));
    $this->drupalLogin($this->web_user);
  }

  // Test fields.

  /**
   * Test text field validation.
   */
  function testTextFieldValidation() {
    // Create a field with settings to validate.
    $max_length = 3;
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'text',
      'settings' => array(
        'max_length' => $max_length,
      )
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
    );
    field_create_instance($this->instance);

    // Test valid and invalid values with field_attach_validate().
    $entity = field_test_create_entity();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    for ($i = 0; $i <= $max_length + 2; $i++) {
      $entity->{$this->field['field_name']}[$langcode][0]['value'] = str_repeat('x', $i);
      try {
        field_attach_validate($entity);
        $this->assertTrue($i <= $max_length, "Length $i does not cause validation error when max_length is $max_length");
      }
      catch (FieldValidationException $e) {
        $this->assertTrue($i > $max_length, "Length $i causes validation error when max_length is $max_length");
      }
    }
  }

  /**
   * Test widgets.
   */
  function testTextfieldWidgets() {
    $this->_testTextfieldWidgets('text', 'text_textfield');
    $this->_testTextfieldWidgets('text_long', 'text_textarea');
  }

  /**
   * Helper function for testTextfieldWidgets().
   */
  function _testTextfieldWidgets($field_type, $widget_type) {
    // Setup a field and instance
    $entity_type = 'test_entity';
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = array('field_name' => $this->field_name, 'type' => $field_type);
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->randomName() . '_label',
      'settings' => array(
        'text_processing' => TRUE,
      ),
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field_name, array(
        'type' => $widget_type,
        'settings' => array(
          'placeholder' => 'A placeholder on ' . $widget_type,
        ),
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field_name)
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$this->field_name}[$langcode][0][format]", '1', 'Format selector is not displayed');
    $this->assertRaw(format_string('placeholder="A placeholder on !widget_type"', array('!widget_type' => $widget_type)));

    // Submit with some value.
    $value = $this->randomName();
    $edit = array(
      "{$this->field_name}[$langcode][0][value]" => $value,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');

    // Display the entity.
    $entity = field_test_entity_test_load($id);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    $entity->content = field_attach_view($entity, $display);
    $this->content = drupal_render($entity->content);
    $this->assertText($value, 'Filtered tags are not displayed');
  }

  /**
   * Test widgets + 'formatted_text' setting.
   */
  function testTextfieldWidgetsFormatted() {
    $this->_testTextfieldWidgetsFormatted('text', 'text_textfield');
    $this->_testTextfieldWidgetsFormatted('text_long', 'text_textarea');
  }

  /**
   * Helper function for testTextfieldWidgetsFormatted().
   */
  function _testTextfieldWidgetsFormatted($field_type, $widget_type) {
    // Setup a field and instance
    $entity_type = 'test_entity';
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = array('field_name' => $this->field_name, 'type' => $field_type);
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->randomName() . '_label',
      'settings' => array(
        'text_processing' => TRUE,
      ),
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field_name, array(
        'type' => $widget_type,
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field_name)
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Disable all text formats besides the plain text fallback format.
    $this->drupalLogin($this->admin_user);
    foreach (filter_formats() as $format) {
      if ($format->format != filter_fallback_format()) {
        $this->drupalPost('admin/config/content/formats/' . $format->format . '/disable', array(), t('Disable'));
      }
    }
    $this->drupalLogin($this->web_user);

    // Display the creation form. Since the user only has access to one format,
    // no format selector will be displayed.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$this->field_name}[$langcode][0][format]", '', 'Format selector is not displayed');

    // Submit with data that should be filtered.
    $value = '<em>' . $this->randomName() . '</em>';
    $edit = array(
      "{$this->field_name}[$langcode][0][value]" => $value,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');

    // Display the entity.
    $entity = field_test_entity_test_load($id);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    $entity->content = field_attach_view($entity, $display);
    $this->content = drupal_render($entity->content);
    $this->assertNoRaw($value, 'HTML tags are not displayed.');
    $this->assertRaw(check_plain($value), 'Escaped HTML is displayed correctly.');

    // Create a new text format that does not escape HTML, and grant the user
    // access to it.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'format' => drupal_strtolower($this->randomName()),
      'name' => $this->randomName(),
    );
    $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
    filter_formats_reset();
    $this->checkPermissions(array(), TRUE);
    $format = filter_format_load($edit['format']);
    $format_id = $format->format;
    $permission = filter_permission_name($format);
    $rid = $this->web_user->roles[0];
    user_role_grant_permissions($rid, array($permission));
    $this->drupalLogin($this->web_user);

    // Display edition form.
    // We should now have a 'text format' selector.
    $this->drupalGet('test-entity/manage/' . $id . '/edit');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][value]", NULL, 'Widget is displayed');
    $this->assertFieldByName("{$this->field_name}[$langcode][0][format]", NULL, 'Format selector is displayed');

    // Edit and change the text format to the new one that was created.
    $edit = array(
      "{$this->field_name}[$langcode][0][format]" => $format_id,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('test_entity @id has been updated.', array('@id' => $id)), 'Entity was updated');

    // Display the entity.
    $this->container->get('plugin.manager.entity')->getStorageController('test_entity')->resetCache(array($id));
    $entity = field_test_entity_test_load($id);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    $entity->content = field_attach_view($entity, $display);
    $this->content = drupal_render($entity->content);
    $this->assertRaw($value, 'Value is displayed unfiltered');
  }
}
