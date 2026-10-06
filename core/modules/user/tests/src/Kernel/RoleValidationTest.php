<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\TestTools\Attribute\ShareEnvironment;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of user_role entities.
 */
#[Group('user')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class RoleValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->installConfig('user');

    $this->entity = Role::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

}
