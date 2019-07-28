<?php

namespace Drupal\Tests\user\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\Validation\Constraint\UserMailRequired;
use Drupal\user\Plugin\Validation\Constraint\UserMailRequiredValidator;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\user\Plugin\Validation\Constraint\UserMailRequiredValidator
 * @group user
 */
class UserMailRequiredValidatorTest extends UnitTestCase {

  /**
   * Creates a validator instance.
   *
   * @param bool $is_admin
   *   Whether or not the current user is an administrator.
   *
   * @return \Drupal\user\Plugin\Validation\Constraint\UserMailRequiredValidator
   *   The validator instance.
   */
  protected function createValidator($is_admin) {
    // Setup mocks that don't need to change.
    $unchanged_account = $this->prophesize(UserInterface::class);
    $unchanged_account->getEmail()->willReturn(NULL);

    $user_storage = $this->prophesize(UserStorageInterface::class);
    $user_storage->loadUnchanged(3)->willReturn($unchanged_account->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage->reveal());

    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->id()->willReturn(3);
    $current_user->hasPermission("administer users")->willReturn($is_admin);
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('current_user', $current_user->reveal());
    \Drupal::setContainer($container);
    return new UserMailRequiredValidator();
  }

  /**
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($items, $expected_violation, $is_admin = FALSE) {
    $constraint = new UserMailRequired();

    // If a violation is expected, then the context's addViolation method will
    // be called, otherwise it should not be called.
    $context = $this->prophesize(ExecutionContextInterface::class);

    if ($expected_violation) {
      $context->addViolation('@name field is required.', ['@name' => 'Email'])->shouldBeCalledTimes(1);
    }
    else {
      $context->addViolation()->shouldNotBeCalled();
    }

    $validator = $this->createValidator($is_admin);
    $validator->initialize($context->reveal());
    $validator->validate($items, $constraint);
  }

  /**
   * Data provider for ::testValidate().
   */
  public function providerTestValidate() {
    $cases = [];

    // Case 1: Empty user should be ignored.
    $items = $this->prophesize(FieldItemListInterface::class);
    $items->getEntity()->willReturn(NULL)->shouldBeCalledTimes(1);
    $cases['Empty user should be ignored'] = [$items->reveal(), FALSE];

    // Case 2: New users without an email should add a violation.
    $items = $this->prophesize(FieldItemListInterface::class);
    $account = $this->prophesize(UserInterface::class);
    $account->isNew()->willReturn(TRUE);
    $account->id()->shouldNotBeCalled();
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('Email');
    $account->getFieldDefinition("mail")->willReturn($field_definition->reveal())->shouldBeCalledTimes(1);
    $items->getEntity()->willReturn($account->reveal())->shouldBeCalledTimes(1);
    $items->isEmpty()->willReturn(TRUE);
    $cases['New users without an email should add a violation'] = [$items->reveal(), TRUE];

    // Case 3: Existing users without an email should add a violation.
    $items = $this->prophesize(FieldItemListInterface::class);
    $account = $this->prophesize(UserInterface::class);
    $account->isNew()->willReturn(FALSE);
    $account->id()->willReturn(3);
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('Email');
    $account->getFieldDefinition("mail")->willReturn($field_definition->reveal())->shouldBeCalledTimes(1);
    $items->getEntity()->willReturn($account->reveal())->shouldBeCalledTimes(1);
    $items->isEmpty()->willReturn(TRUE);
    $cases['Existing users without an email should add a violation'] = [$items->reveal(), TRUE];

    // Case 4: New user with an e-mail is valid.
    $items = $this->prophesize(FieldItemListInterface::class);
    $account = $this->prophesize(UserInterface::class);
    $account->isNew()->willReturn(TRUE);
    $account->id()->shouldNotBeCalled();
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('Email');
    $account->getFieldDefinition("mail")->willReturn($field_definition->reveal())->shouldBeCalledTimes(1);
    $items->getEntity()->willReturn($account->reveal())->shouldBeCalledTimes(1);
    $items->isEmpty()->willReturn(FALSE);
    $cases['New user with an e-mail is valid'] = [$items->reveal(), FALSE];

    // Case 5: Existing users with an email should be ignored.
    $items = $this->prophesize(FieldItemListInterface::class);
    $account = $this->prophesize(UserInterface::class);
    $account->isNew()->willReturn(FALSE);
    $account->id()->willReturn(3);
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('Email');
    $account->getFieldDefinition("mail")->willReturn($field_definition->reveal())->shouldBeCalledTimes(1);
    $items->getEntity()->willReturn($account->reveal())->shouldBeCalledTimes(1);
    $items->isEmpty()->willReturn(FALSE);
    $cases['Existing users with an email should be ignored'] = [$items->reveal(), FALSE];

    // Case 6: Existing users without an email should be ignored if the current
    // user is an administrator.
    $items = $this->prophesize(FieldItemListInterface::class);
    $account = $this->prophesize(UserInterface::class);
    $account->isNew()->willReturn(FALSE);
    $account->id()->willReturn(3);
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('Email');
    $account->getFieldDefinition("mail")->willReturn($field_definition->reveal())->shouldBeCalledTimes(1);
    $items->getEntity()->willReturn($account->reveal())->shouldBeCalledTimes(1);
    $items->isEmpty()->willReturn(TRUE);
    $cases['Existing users without an email should be ignored if the current user is an administrator.'] = [$items->reveal(), FALSE, TRUE];

    return $cases;
  }

}
