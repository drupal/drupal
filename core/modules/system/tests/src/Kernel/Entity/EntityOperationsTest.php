<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that operations can be injected from the hook.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityOperationsTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    // Create and log in user.
    $this->setCurrentUser($this->drupalCreateUser(['administer permissions']));
  }

  /**
   * Checks that hook_entity_operation_alter() can add an operation.
   *
   * @see entity_test_entity_operation_alter()
   */
  public function testEntityOperationAlter(): void {
    // Check that role listing contain our test_operation operation.
    $this->drupalGet('admin/people/roles');
    $roles = Role::loadMultiple();
    foreach ($roles as $role) {
      $this->assertSession()->linkByHrefExists($role->toUrl()->toString() . '/test_operation');
      $this->assertSession()->linkExists('Test Operation: ' . $role->label());
    }
  }

}
