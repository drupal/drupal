<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserEntityCallbacksTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user entity callbacks.
 */
class UserEntityCallbacksTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User entity callback tests',
      'description' => 'Tests specific parts of the user entity like the URI callback and the label callback.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp('user');

    $this->account = $this->drupalCreateUser();
    $this->anonymous = drupal_anonymous_user();
  }

  /**
   * Test label callback.
   */
  function testLabelCallback() {
    $this->assertEqual(entity_label('user', $this->account), $this->account->name, t('The username should be used as label'));

    // Setup a random anonymous name to be sure the name is used.
    $name = $this->randomName();
    variable_set('anonymous', $name);
    $this->assertEqual(entity_label('user', $this->anonymous), $name, t('The variable anonymous should be used for name of uid 0'));
  }

  /**
   * Test URI callback.
   */
  function testUriCallback() {
    $uri = entity_uri('user', $this->account);
    $this->assertEqual('user/' . $this->account->uid, $uri['path'], t('Correct user URI.'));
  }
}
