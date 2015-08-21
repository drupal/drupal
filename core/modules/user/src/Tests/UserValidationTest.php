<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserValidationTest.
 */

namespace Drupal\user\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Element\Email;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Verify that user validity checks behave as designed.
 *
 * @group user
 */
class UserValidationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'user', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', array('sequences'));

    // Make sure that the default roles exist.
    $this->installConfig(array('user'));

  }

  /**
   * Tests user name validation.
   */
  function testUsernames() {
    $test_cases = array( // '<username>' => array('<description>', 'assert<testName>'),
      'foo'                    => array('Valid username', 'assertNull'),
      'FOO'                    => array('Valid username', 'assertNull'),
      'Foo O\'Bar'             => array('Valid username', 'assertNull'),
      'foo@bar'                => array('Valid username', 'assertNull'),
      'foo@example.com'        => array('Valid username', 'assertNull'),
      'foo@-example.com'       => array('Valid username', 'assertNull'), // invalid domains are allowed in usernames
      'þòøÇßªř€'               => array('Valid username', 'assertNull'),
      'ᚠᛇᚻ᛫ᛒᛦᚦ'                => array('Valid UTF8 username', 'assertNull'), // runes
      ' foo'                   => array('Invalid username that starts with a space', 'assertNotNull'),
      'foo '                   => array('Invalid username that ends with a space', 'assertNotNull'),
      'foo  bar'               => array('Invalid username that contains 2 spaces \'&nbsp;&nbsp;\'', 'assertNotNull'),
      ''                       => array('Invalid empty username', 'assertNotNull'),
      'foo/'                   => array('Invalid username containing invalid chars', 'assertNotNull'),
      'foo' . chr(0) . 'bar'   => array('Invalid username containing chr(0)', 'assertNotNull'), // NULL
      'foo' . chr(13) . 'bar'  => array('Invalid username containing chr(13)', 'assertNotNull'), // CR
      str_repeat('x', USERNAME_MAX_LENGTH + 1) => array('Invalid excessively long username', 'assertNotNull'),
    );
    foreach ($test_cases as $name => $test_case) {
      list($description, $test) = $test_case;
      $result = user_validate_name($name);
      $this->$test($result, $description . ' (' . $name . ')');
    }
  }

  /**
   * Runs entity validation checks.
   */
  function testValidation() {
    $user = User::create(array(
      'name' => 'test',
      'mail' => 'test@example.com',
    ));
    $violations = $user->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default user.');

    // Only test one example invalid name here, the rest is already covered in
    // the testUsernames() method in this class.
    $name = $this->randomMachineName(61);
    $user->set('name', $name);
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when name is too long.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'name');
    $this->assertEqual($violations[0]->getMessage(), t('The username %name is too long: it must be %max characters or less.', array('%name' => $name, '%max' => 60)));

    // Create a second test user to provoke a name collision.
    $user2 = entity_create('user', array(
      'name' => 'existing',
      'mail' => 'existing@example.com',
    ));
    $user2->save();
    $user->set('name', 'existing');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found on name collision.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'name');
    $this->assertEqual($violations[0]->getMessage(), t('The username %name is already taken.', array('%name' => 'existing')));

    // Make the name valid.
    $user->set('name', $this->randomMachineName());

    $user->set('mail', 'invalid');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when email is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value is not a valid email address.'));

    $mail = $this->randomMachineName(Email::EMAIL_MAX_LENGTH - 11) . '@example.com';
    $user->set('mail', $mail);
    $violations = $user->validate();
    // @todo There are two violations because EmailItem::getConstraints()
    //   overlaps with the implicit constraint of the 'email' property type used
    //   in EmailItem::propertyDefinitions(). Resolve this in
    //   https://www.drupal.org/node/2023465.
    $this->assertEqual(count($violations), 2, 'Violations found when email is too long');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('%name: the email address can not be longer than @max characters.', array('%name' => $user->get('mail')->getFieldDefinition()->getLabel(), '@max' => Email::EMAIL_MAX_LENGTH)));
    $this->assertEqual($violations[1]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[1]->getMessage(), t('This value is not a valid email address.'));

    // Provoke an email collision with an existing user.
    $user->set('mail', 'existing@example.com');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when email already exists.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail');
    $this->assertEqual($violations[0]->getMessage(), t('The email address %mail is already taken.', array('%mail' => 'existing@example.com')));
    $user->set('mail', NULL);
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'E-mail addresses may not be removed');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail');
    $this->assertEqual($violations[0]->getMessage(), t('!name field is required.', array('!name' => $user->getFieldDefinition('mail')->getLabel())));
    $user->set('mail', 'someone@example.com');

    $user->set('timezone', $this->randomString(33));
    $this->assertLengthViolation($user, 'timezone', 32, 2, 1);
    $user->set('timezone', 'invalid zone');
    $this->assertAllowedValuesViolation($user, 'timezone');
    $user->set('timezone', NULL);

    $user->set('init', 'invalid');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when init email is invalid');
    $user->set('init', NULL);

    $user->set('langcode', 'invalid');
    $this->assertAllowedValuesViolation($user, 'langcode');
    $user->set('langcode', NULL);

    // Only configurable langcodes are allowed for preferred languages.
    $user->set('preferred_langcode', Language::LANGCODE_NOT_SPECIFIED);
    $this->assertAllowedValuesViolation($user, 'preferred_langcode');
    $user->set('preferred_langcode', NULL);

    $user->set('preferred_admin_langcode', Language::LANGCODE_NOT_SPECIFIED);
    $this->assertAllowedValuesViolation($user, 'preferred_admin_langcode');
    $user->set('preferred_admin_langcode', NULL);

    Role::create(array('id' => 'role1'))->save();
    Role::create(array('id' => 'role2'))->save();

    // Test cardinality of user roles.
    $user = entity_create('user', array(
      'name' => 'role_test',
      'mail' => 'test@example.com',
      'roles' => array('role1', 'role2'),
    ));
    $violations = $user->validate();
    $this->assertEqual(count($violations), 0);

    $user->roles[1]->target_id = 'unknown_role';
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1);
    $this->assertEqual($violations[0]->getPropertyPath(), 'roles.1');
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%entity_type: %name) does not exist.', array('%entity_type' => 'user_role', '%name' => 'unknown_role')));
  }

  /**
   * Verifies that a length violation exists for the given field.
   *
   * @param \Drupal\core\Entity\EntityInterface $entity
   *   The entity object to validate.
   * @param string $field_name
   *   The field that violates the maximum length.
   * @param int $length
   *   Number of characters that was exceeded.
   * @param int $count
   *   (optional) The number of expected violations. Defaults to 1.
   * @param int $expected_index
   *   (optional) The index at which to expect the violation. Defaults to 0.
   */
  protected function assertLengthViolation(EntityInterface $entity, $field_name, $length, $count = 1, $expected_index = 0) {
    $violations = $entity->validate();
    $this->assertEqual(count($violations), $count, "Violation found when $field_name is too long.");
    $this->assertEqual($violations[$expected_index]->getPropertyPath(), "$field_name.0.value");
    $field_label = $entity->get($field_name)->getFieldDefinition()->getLabel();
    $this->assertEqual($violations[$expected_index]->getMessage(), t('%name: may not be longer than @max characters.', array('%name' => $field_label, '@max' => $length)));
  }

  /**
   * Verifies that a AllowedValues violation exists for the given field.
   *
   * @param \Drupal\core\Entity\EntityInterface $entity
   *   The entity object to validate.
   * @param string $field_name
   *   The name of the field to verify.
   */
  protected function assertAllowedValuesViolation(EntityInterface $entity, $field_name) {
    $violations = $entity->validate();
    $this->assertEqual(count($violations), 1, "Allowed values violation for $field_name found.");
    $this->assertEqual($violations[0]->getPropertyPath(), "$field_name.0.value");
    $this->assertEqual($violations[0]->getMessage(), t('The value you selected is not a valid choice.'));
  }

}
