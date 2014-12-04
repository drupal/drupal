<?php

/**
 * @file
 * Definition of \Drupal\field\Tests\String\StringFieldTest.
 */

namespace Drupal\field\Tests\String;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of string fields.
 *
 * @group text
 */
class StringFieldTest extends WebTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * A user without any special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($this->webUser);
  }

  // Test fields.

  /**
   * Test widgets.
   */
  function testTextfieldWidgets() {
    $this->_testTextfieldWidgets('string', 'string_textfield');
    $this->_testTextfieldWidgets('string_long', 'string_textarea');
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
}
