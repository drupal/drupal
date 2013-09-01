<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserValidationTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

class UserValidationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Username/e-mail validation',
      'description' => 'Verify that username/email validity checks behave as designed.',
      'group' => 'User'
    );
  }

  // Username validation.
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
}
