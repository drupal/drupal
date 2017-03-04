<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that operations can be injected from the hook.
 *
 * @group Entity
 */
class EntityOperationsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test'];

  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $this->drupalLogin($this->drupalCreateUser(['administer permissions']));
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
      $this->assertLink(format_string('Test Operation: @label', ['@label' => $role->label()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createRole(array $permissions, $rid = NULL, $name = NULL, $weight = NULL) {
    // WebTestBase::drupalCreateRole() by default uses random strings which may
    // include HTML entities for the entity label. Since in this test the entity
    // label is used to generate a link, and AssertContentTrait::assertLink() is
    // not designed to deal with links potentially containing HTML entities this
    // causes random failures. Use a random HTML safe string instead.
    $name = $name ?: $this->randomMachineName();
    return parent::createRole($permissions, $rid, $name, $weight);
  }

}
