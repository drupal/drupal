<?php

/**
 * @file
 * Definition of Drupal\email\Tests\EmailFieldTest.
 */

namespace Drupal\email\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests e-mail field functionality.
 */
class EmailFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_test', 'email', 'field_ui');

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\Field
   */
  protected $field;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  public static function getInfo() {
    return array(
      'name'  => 'E-mail field',
      'description'  => 'Tests e-mail field functionality.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array(
      'view test entity',
      'administer entity_test content',
      'administer content types',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests e-mail field.
   */
  function testEmailField() {
    // Create a field with settings to validate.
    $field_name = drupal_strtolower($this->randomName());
    $this->field = entity_create('field_entity', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'email',
    ));
    $this->field->save();
    $this->instance = entity_create('field_instance', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $this->instance->save();

    // Create a form display for the default form mode.
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'email_default',
        'settings' => array(
          'placeholder' => 'example@example.com',
        ),
      ))
      ->save();
    // Create a display for the full view mode.
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, array(
        'type' => 'email_mailto',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $this->assertFieldByName("{$field_name}[$langcode][0][value]", '', 'Widget found.');
    $this->assertRaw('placeholder="example@example.com"');

    // Submit a valid e-mail address and ensure it is accepted.
    $value = 'test@example.com';
    $edit = array(
      'user_id' => 1,
      'name' => $this->randomName(),
      "{$field_name}[$langcode][0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($value);

    // Verify that a mailto link is displayed.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    $entity->content = field_attach_view($entity, $display);
    $this->drupalSetContent(drupal_render($entity->content));
    $this->assertLinkByHref('mailto:test@example.com');
  }
}
