<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\Validation\Constraint;

use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraint;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator
 * @group user
 */
class ProtectedUserFieldConstraintValidatorTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function createValidator() {
    // Setup mocks that don't need to change.
    $unchanged_field = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $unchanged_field->expects($this->any())
      ->method('getValue')
      ->willReturn('unchanged-value');

    $unchanged_account = $this->createMock('Drupal\user\UserInterface');
    $unchanged_account->expects($this->any())
      ->method('get')
      ->willReturn($unchanged_field);
    $user_storage = $this->createMock('Drupal\user\UserStorageInterface');
    $user_storage->expects($this->any())
      ->method('loadUnchanged')
      ->willReturn($unchanged_account);
    $current_user = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $current_user->expects($this->any())
      ->method('id')
      ->willReturn('current-user');
    return new ProtectedUserFieldConstraintValidator($user_storage, $current_user);
  }

  /**
   * Perform the validation.
   */
  protected function validate($items, ?string $name = NULL): void {
    $constraint = new ProtectedUserFieldConstraint();

    // If a violation is expected, then the context's addViolation method will
    // be called, otherwise it should not be called.
    $context = $this->createMock(ExecutionContextInterface::class);

    if ($name) {
      $context->expects($this->once())
        ->method('addViolation')
        ->with($constraint->message, ['%name' => $name]);
    }
    else {
      $context->expects($this->never())
        ->method('addViolation');
    }

    $validator = $this->createValidator();
    $validator->initialize($context);
    $validator->validate($items, $constraint);
  }

  /**
   * @covers ::validate
   */
  public function testValidate(): void {
    // Case 1: Validation context should not be touched if no items are passed.
    $this->validate(NULL);

    // Case 2: Empty user should be ignored.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn(NULL);
    $this->validate($items);

    // Case 3: Account flagged to skip protected user should be ignored.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $account = $this->createMock(User::class);
    $account->_skipProtectedUserFieldConstraint = TRUE;
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $this->validate($items);

    // Case 4: New user should be ignored.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $this->validate($items);

    // Case 5: Mismatching user IDs should also be ignored.
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->once())
      ->method('id')
      ->willReturn('not-current-user');
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $this->validate($items);

    // Case 6: Non-password fields that have not changed should be ignored.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->never())
      ->method('checkExistingPassword');
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('unchanged-value');
    $this->validate($items);

    // Case 7: Password field with no value set should be ignored.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->once())
      ->method('getName')
      ->willReturn('pass');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->never())
      ->method('checkExistingPassword');
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $this->validate($items);

    // Case 8: Non-password field changed, but user has passed provided current
    // password.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(TRUE);
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('changed-value');
    $this->validate($items);

    // Case 9: Password field changed, current password confirmed.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('pass');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(TRUE);
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->any())
      ->method('getValue')
      ->willReturn('changed-value');
    $items->expects($this->once())
      ->method('__get')
      ->with('value')
      ->willReturn('changed-value');
    $this->validate($items);

    // The below calls should result in a violation.

    // Case 10: Password field changed, current password not confirmed.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('pass');
    $field_definition->expects($this->any())
      ->method('getLabel')
      ->willReturn('Password');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(FALSE);
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('changed-value');
    $items->expects($this->once())
      ->method('__get')
      ->with('value')
      ->willReturn('changed-value');
    $this->validate($items, 'Password');

    // Case 11: Non-password field changed, current password not confirmed.
    $field_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $field_definition->expects($this->any())
      ->method('getLabel')
      ->willReturn('Protected field');
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(FALSE);
    $items = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('changed-value');
    $this->validate($items, 'Protected field');
  }

}
