<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserValidationTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Performs validation tests on user fields.
 */
class UserValidationTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'field', 'user', 'system');

  public static function getInfo() {
    return array(
      'name' => 'User validation',
      'description' => 'Verify that user validity checks behave as designed.',
      'group' => 'User'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users'));
    $this->installSchema('system', array('sequences'));
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
    $user = entity_create('user', array('name' => 'test'));
    $violations = $user->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default user.');

    // Only test one example invalid name here, the rest is already covered in
    // the testUsernames() method in this class.
    $name = $this->randomName(61);
    $user->set('name', $name);
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when name is too long.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'name.0.value');
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
    $this->assertEqual($violations[0]->getPropertyPath(), 'name.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('The name %name is already taken.', array('%name' => 'existing')));

    // Make the name valid.
    $user->set('name', $this->randomName());

    $user->set('mail', 'invalid');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when email is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value is not a valid email address.'));

    $mail = $this->randomName(EMAIL_MAX_LENGTH - 11) . '@example.com';
    $user->set('mail', $mail);
    $violations = $user->validate();
    // @todo There are two violations because EmailItem::getConstraints()
    //   overlaps with the implicit constraint of the 'email' property type used
    //   in EmailItem::propertyDefinitions(). Resolve this in
    //   https://drupal.org/node/2023465.
    $this->assertEqual(count($violations), 2, 'Violations found when email is too long');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('%name: the e-mail address can not be longer than @max characters.', array('%name' => $user->get('mail')->getFieldDefinition()->getLabel(), '@max' => EMAIL_MAX_LENGTH)));
    $this->assertEqual($violations[1]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[1]->getMessage(), t('This value is not a valid email address.'));

    // Provoke a e-mail collision with an exsiting user.
    $user->set('mail', 'existing@example.com');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when e-mail already exists.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('The e-mail address %mail is already taken.', array('%mail' => 'existing@example.com')));
    $user->set('mail', NULL);

    $user->set('signature', $this->randomString(256));
    $this->assertLengthViolation($user, 'signature', 255);
    $user->set('signature', NULL);

    $user->set('timezone', $this->randomString(33));
    $this->assertLengthViolation($user, 'timezone', 32);
    $user->set('timezone', NULL);

    $user->set('init', 'invalid');
    $violations = $user->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when init email is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'init.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value is not a valid email address.'));

    // Test cardinality of user roles.
    $user = entity_create('user', array(
      'name' => 'role_test',
      'roles' => array('role1', 'role2'),
    ));
    $violations = $user->validate();
    $this->assertEqual(count($violations), 0);
    // @todo Test user role validation once https://drupal.org/node/2044859 got
    // committed.
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
  */
  protected function assertLengthViolation(EntityInterface $entity, $field_name, $length) {
    $violations = $entity->validate();
    $this->assertEqual(count($violations), 1, "Violation found when $field_name is too long.");
    $this->assertEqual($violations[0]->getPropertyPath(), "$field_name.0.value");
    $field_label = $entity->get($field_name)->getFieldDefinition()->getLabel();
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', array('%name' => $field_label, '@max' => $length)));
  }

}
