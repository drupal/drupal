<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests validation of user_role entities.
 */
#[Group('user')]
class RoleValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');

    $this->entity = Role::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

}
