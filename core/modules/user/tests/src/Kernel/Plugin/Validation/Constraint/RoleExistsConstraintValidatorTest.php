<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @group user
 * @group Validation
 *
 * @covers \Drupal\user\Plugin\Validation\Constraint\RoleExistsConstraint
 * @covers \Drupal\user\Plugin\Validation\Constraint\RoleExistsConstraintValidator
 */
class RoleExistsConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Tests that the constraint validator will only work with strings.
   */
  public function testValueMustBeAString(): void {
    $definition = DataDefinition::create('any')
      ->addConstraint('RoleExists');

    $this->expectException(UnexpectedTypeException::class);
    $this->expectExceptionMessage('Expected argument of type "string", "int" given');
    $this->container->get('typed_data_manager')
      ->create($definition, 39)
      ->validate();
  }

  /**
   * Tests when the constraint's entityTypeId value is not valid.
   */
  public function testRoleExists(): void {
    // Validation error when role does not exist.
    $definition = DataDefinition::create('string')
      ->addConstraint('RoleExists');

    $violations = $this->container->get('typed_data_manager')
      ->create($definition, 'test_role')
      ->validate();
    $this->assertEquals('The role with id \'test_role\' does not exist.', $violations->get(0)->getMessage());
    $this->assertCount(1, $violations);

    // Validation success when role exists.
    Role::create(['id' => 'test_role', 'label' => 'Test role'])->save();
    $definition = DataDefinition::create('string')
      ->addConstraint('RoleExists');

    $violations = $this->container->get('typed_data_manager')
      ->create($definition, 'test_role')
      ->validate();
    $this->assertCount(0, $violations);
  }

}
