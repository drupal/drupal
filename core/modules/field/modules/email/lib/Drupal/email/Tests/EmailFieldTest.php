<?php

/**
 * @file
 * Definition of Drupal\email\Tests\EmailFieldTest.
 */

namespace Drupal\email\Tests;

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
  public static $modules = array('node', 'field_test', 'email', 'field_ui');

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
      'access field_test content',
      'administer field_test content',
      'administer content types',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests e-mail field.
   */
  function testEmailField() {
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'email',
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'email_default',
      ),
      'display' => array(
        'full' => array(
          'type' => 'email_mailto',
        ),
      ),
    );
    field_create_instance($this->instance);

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $this->assertFieldByName("{$this->field['field_name']}[$langcode][0][value]", '', 'Widget found.');

    // Submit a valid e-mail address and ensure it is accepted.
    $value = 'test@example.com';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][value]" => $value,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)));
    $this->assertRaw($value);

    // Verify that a mailto link is displayed.
    $entity = field_test_entity_test_load($id);
    $entity->content = field_attach_view('test_entity', $entity, 'full');
    $this->drupalSetContent(drupal_render($entity->content));
    $this->assertLinkByHref('mailto:test@example.com');
  }
}
