<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAuthmapAssignmentTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Unit test for authmap assignment.
 */
class UserAuthmapAssignmentTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => t('Authmap assignment'),
      'description' => t('Tests that users can be assigned and unassigned authmaps.'),
      'group' => t('User')
    );
  }

  /**
   * Test authmap assignment and retrieval.
   */
  function testAuthmapAssignment()  {
    $account = $this->drupalCreateUser();

    // Assign authmaps to the user.
    $authmaps = array(
      'authname_poll' => 'external username one',
      'authname_book' => 'external username two',
    );
    user_set_authmaps($account, $authmaps);

    // Test for expected authmaps.
    $expected_authmaps = array(
      'external username one' => array(
        'poll' => 'external username one',
      ),
      'external username two' => array(
        'book' => 'external username two',
      ),
    );
    foreach ($expected_authmaps as $authname => $expected_output) {
      $this->assertIdentical(user_get_authmaps($authname), $expected_output, t('Authmap for authname %authname was set correctly.', array('%authname' => $authname)));
    }

    // Remove authmap for module poll, add authmap for module blog.
    $authmaps = array(
      'authname_poll' => NULL,
      'authname_blog' => 'external username three',
    );
    user_set_authmaps($account, $authmaps);

    // Assert that external username one does not have authmaps.
    $remove_username = 'external username one';
    unset($expected_authmaps[$remove_username]);
    $this->assertFalse(user_get_authmaps($remove_username), t('Authmap for %authname was removed.', array('%authname' => $remove_username)));

    // Assert that a new authmap was created for external username three, and
    // existing authmaps for external username two were unchanged.
    $expected_authmaps['external username three'] = array('blog' => 'external username three');
    foreach ($expected_authmaps as $authname => $expected_output) {
      $this->assertIdentical(user_get_authmaps($authname), $expected_output, t('Authmap for authname %authname was set correctly.', array('%authname' => $authname)));
    }
  }
}
