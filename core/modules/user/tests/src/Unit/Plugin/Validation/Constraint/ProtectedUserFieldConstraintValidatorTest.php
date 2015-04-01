<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidatorTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\Validation\Constraint;

use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraint;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;

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
    $unchanged_field = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $unchanged_field->expects($this->any())
      ->method('getValue')
      ->willReturn('unchanged-value');

    $unchanged_account = $this->getMock('Drupal\user\UserInterface');
    $unchanged_account->expects($this->any())
      ->method('get')
      ->willReturn($unchanged_field);
    $user_storage = $this->getMock('Drupal\user\UserStorageInterface');
    $user_storage->expects($this->any())
      ->method('loadUnchanged')
      ->willReturn($unchanged_account);
    $current_user = $this->getMock('Drupal\Core\Session\AccountProxyInterface');
    $current_user->expects($this->any())
      ->method('id')
      ->willReturn('current-user');
    return new ProtectedUserFieldConstraintValidator($user_storage, $current_user);
  }

  /**
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($items, $expected_violation, $name = FALSE) {
    $constraint = new ProtectedUserFieldConstraint();

    // If a violation is expected, then the context's addViolation method will
    // be called, otherwise it should not be called.
    $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

    if ($expected_violation) {
      $context->expects($this->once())
        ->method('addViolation')
        ->with($constraint->message, array('%name' => $name));
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
   * Data provider for ::testValidate().
   */
  public function providerTestValidate() {
    $cases = [];

    // Case 1: Validation context should not be touched if no items are passed.
    $cases[] = [NULL, FALSE];

    // Case 2: Empty user should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn(NULL);
    $cases[] = [$items, FALSE];

    // Case 3: Account flagged to skip protected user should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->_skipProtectedUserFieldConstraint = TRUE;
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $cases[] = [$items, FALSE];

    // Case 4: New user should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $cases[] = [$items, FALSE];

    // Case 5: Mismatching user IDs should also be ignored.
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->once())
      ->method('id')
      ->willReturn('not-current-user');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $cases[] = [$items, FALSE];

    // Case 6: Non-password fields that have not changed should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->never())
      ->method('checkExistingPassword');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('unchanged-value');
    $cases[] = [$items, FALSE];

    // Case 7: Password field with no value set should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->once())
      ->method('getName')
      ->willReturn('pass');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->never())
      ->method('checkExistingPassword');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $cases[] = [$items, FALSE];

    // Case 8: Non-password field changed, but user has passed provided current
    // password.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(TRUE);
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('changed-value');
    $cases[] = [$items, FALSE];

    // Case 9: Password field changed, current password confirmed.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('pass');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(TRUE);
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
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
    $cases[] = [$items, FALSE];

    // The below calls should result in a violation.

    // Case 10: Password field changed, current password not confirmed.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('pass');
    $field_definition->expects($this->any())
      ->method('getLabel')
      ->willReturn('Password');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(FALSE);
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
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
    $cases[] = [$items, TRUE, 'Password'];

    // Case 11: Non-password field changed, current password not confirmed.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('field_not_password');
    $field_definition->expects($this->any())
      ->method('getLabel')
      ->willReturn('Protected field');
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);
    $account->expects($this->exactly(2))
      ->method('id')
      ->willReturn('current-user');
    $account->expects($this->once())
      ->method('checkExistingPassword')
      ->willReturn(FALSE);
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn($account);
    $items->expects($this->once())
      ->method('getValue')
      ->willReturn('changed-value');
    $cases[] = [$items, TRUE, 'Protected field'];

    return $cases;
  }

}
