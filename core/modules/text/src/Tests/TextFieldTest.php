<?php

/**
 * @file
 * Definition of Drupal\text\TextFieldTest.
 */

namespace Drupal\text\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of text fields.
 *
 * @group text
 */
class TextFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  protected $admin_user;
  protected $web_user;

  protected function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer filters'));
    $this->web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($this->web_user);
  }

  // Test fields.

  /**
   * Test text field validation.
   */
  function testTextFieldValidation() {
    // Create a field with settings to validate.
    $max_length = 3;
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text',
      'settings' => array(
        'max_length' => $max_length,
      )
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Test validation with valid and invalid values.
    $entity = entity_create('entity_test');
    for ($i = 0; $i <= $max_length + 2; $i++) {
      $entity->{$field_name}->value = str_repeat('x', $i);
      $violations = $entity->{$field_name}->validate();
      if ($i <= $max_length) {
        $this->assertEqual(count($violations), 0, "Length $i does not cause validation error when max_length is $max_length");
      }
      else {
        $this->assertEqual(count($violations), 1, "Length $i causes validation error when max_length is $max_length");
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
    // Create a field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $field_type
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => $widget_type,
        'settings' => array(
          'placeholder' => 'A placeholder on ' . $widget_type,
        ),
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$field_name}[0][format]", '1', 'Format selector is not displayed');
    $this->assertRaw(format_string('placeholder="A placeholder on !widget_type"', array('!widget_type' => $widget_type)));

    // Submit with some value.
    $value = $this->randomMachineName();
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');

    // Display the entity.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));
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
    // Create a field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $field_type
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => $widget_type,
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Disable all text formats besides the plain text fallback format.
    $this->drupalLogin($this->admin_user);
    foreach (filter_formats() as $format) {
      if (!$format->isFallbackFormat()) {
        $this->drupalPostForm('admin/config/content/formats/manage/' . $format->format . '/disable', array(), t('Disable'));
      }
    }
    $this->drupalLogin($this->web_user);

    // Display the creation form. Since the user only has access to one format,
    // no format selector will be displayed.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$field_name}[0][format]", '', 'Format selector is not displayed');

    // Submit with data that should be filtered.
    $value = '<em>' . $this->randomMachineName() . '</em>';
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');

    // Display the entity.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));
    $this->assertNoRaw($value, 'HTML tags are not displayed.');
    $this->assertEscaped($value, 'Escaped HTML is displayed correctly.');

    // Create a new text format that does not escape HTML, and grant the user
    // access to it.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'format' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
    );
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    filter_formats_reset();
    $format = entity_load('filter_format', $edit['format']);
    $format_id = $format->format;
    $permission = $format->getPermissionName();
    $roles = $this->web_user->getRoles();
    $rid = $roles[0];
    user_role_grant_permissions($rid, array($permission));
    $this->drupalLogin($this->web_user);

    // Display edition form.
    // We should now have a 'text format' selector.
    $this->drupalGet('entity_test/manage/' . $id);
    $this->assertFieldByName("{$field_name}[0][value]", NULL, 'Widget is displayed');
    $this->assertFieldByName("{$field_name}[0][format]", NULL, 'Format selector is displayed');

    // Edit and change the text format to the new one that was created.
    $edit = array(
      "{$field_name}[0][format]" => $format_id,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', array('@id' => $id)), 'Entity was updated');

    // Display the entity.
    $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));
    $this->assertRaw($value, 'Value is displayed unfiltered');
  }

}
