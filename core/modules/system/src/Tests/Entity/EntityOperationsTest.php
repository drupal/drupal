<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityOperationsTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for entity operations, that they can be altered.
 */
class EntityOperationsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity Operations',
      'description' => 'Check that operations can be injected from the hook.',
      'group' => 'Entity API',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create and login user.
    $this->web_user = $this->drupalCreateUser(array(
      'administer permissions',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Checks that hook_entity_operation_alter() can add an operation.
   *
   * @see entity_test_entity_operation_alter()
   */
  public function testEntityOperationAlter() {
    // Check that role listing contain our test_operation operation.
    $this->drupalGet('admin/people/roles');
    $roles = user_roles();
    foreach ($roles as $role) {
      $this->assertLinkByHref($role->url() . '/test_operation');
      $this->assertLink(format_string('Test Operation: @label', array('@label' => $role->label())));
    }
  }

}
