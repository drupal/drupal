<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\Core\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests\Drupal\user\Entity\Role.
 */
#[CoversClass(Role::class)]
#[Group('user')]
class RoleTest extends UnitTestCase {

  /**
   * Tests getting the role's description.
   */
  public function testGetDescription(): void {
    $role = new Role([
      'id' => 'test_role',
      'description' => 'Lorem ipsum.',
    ], 'user_role');
    $this->assertSame('Lorem ipsum.', $role->getDescription());
  }

  /**
   * Tests getting the role's description when it is not set.
   */
  public function testGetEmptyDescription(): void {
    $role = new Role([
      'id' => 'test_role',
    ], 'user_role');
    $this->assertSame('', $role->getDescription());
  }

  /**
   * Tests setting and getting the role's description.
   */
  public function testSetDescription(): void {
    $role = new Role([
      'id' => 'test_role',
    ], 'user_role');

    $this->assertSame($role, $role->setDescription('Lorem ipsum.'));
    $this->assertSame('Lorem ipsum.', $role->getDescription());
  }

}
