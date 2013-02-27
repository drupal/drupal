<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\FieldAccessTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the functionality of field access.
 */
class FieldAccessTest extends DrupalUnitTestBase {

  /**
   * Modules to load code from (no schema installation needed).
   *
   * @var array
   */
  public static $modules = array('field_sql_storage', 'system', 'text');

  public static function getInfo() {
    return array(
      'name' => 'Field access tests',
      'description' => 'Test Field level access hooks.',
      'group' => 'Entity API',
    );
  }

  protected function setUp() {
    parent::setUp();
    // Install field and user module schema, register entity_test text field.
    $this->enableModules(array('field', 'entity_test', 'user'));
  }

  /**
   * Tests hook_entity_field_access() and hook_entity_field_access_alter().
   *
   * @see entity_test_entity_field_access()
   * @see entity_test_entity_field_access_alter()
   */
  function testFieldAccess() {
    $values = array(
      'name' => $this->randomName(),
      'user_id' => 1,
      'field_test_text' => array(
        'value' => 'no access value',
        'format' => 'full_html',
      ),
    );
    $entity = entity_create('entity_test', $values);
    $this->assertFalse($entity->field_test_text->access('view'), 'Access to the field was denied.');

    $entity->field_test_text = 'access alter value';
    $this->assertFalse($entity->field_test_text->access('view'), 'Access to the field was denied.');

    $entity->field_test_text = 'standard value';
    $this->assertTrue($entity->field_test_text->access('view'), 'Access to the field was granted.');
  }
}
