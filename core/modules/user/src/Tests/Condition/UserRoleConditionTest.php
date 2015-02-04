<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Condition\UserRoleConditionTest.
 */

namespace Drupal\user\Tests\Condition;

use Drupal\Component\Utility\String;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the user role condition.
 *
 * @group user
 */
class UserRoleConditionTest extends KernelTestBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * An anonymous user for testing purposes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymous;

  /**
   * An authenticated user for testing purposes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticated;

  /**
   * A custom role for testing purposes.
   *
   * @var \Drupal\user\Entity\RoleInterface
   */
  protected $role;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');

    $this->manager = $this->container->get('plugin.manager.condition');

    // Set up the authenticated and anonymous roles.
    Role::create(array(
      'id' => DRUPAL_ANONYMOUS_RID,
      'label' => 'Anonymous user',
    ))->save();
    Role::create(array(
      'id' => DRUPAL_AUTHENTICATED_RID,
      'label' => 'Authenticated user',
    ))->save();

    // Create new role.
    $rid = strtolower($this->randomMachineName(8));
    $label = $this->randomString(8);
    $role = Role::create(array(
      'id' => $rid,
      'label' => $label,
    ));
    $role->save();
    $this->role = $role;

    // Setup an anonymous user for our tests.
    $this->anonymous = User::create(array(
      'uid' => 0,
    ));
    $this->anonymous->save();
    // Loading the anonymous user adds the correct role.
    $this->anonymous = User::load($this->anonymous->id());

    // Setup an authenticated user for our tests.
    $this->authenticated = User::create(array(
      'name' => $this->randomMachineName(),
    ));
    $this->authenticated->save();
    // Add the custom role.
    $this->authenticated->addRole($this->role->id());
  }

  /**
   * Test the user_role condition.
   */
  public function testConditions() {
    // Grab the user role condition and configure it to check against
    // authenticated user roles.
    /** @var $condition \Drupal\Core\Condition\ConditionInterface */
    $condition = $this->manager->createInstance('user_role')
      ->setConfig('roles', array(DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID))
      ->setContextValue('user', $this->anonymous);
    $this->assertFalse($condition->execute(), 'Anonymous users fail role checks for authenticated.');
    // Check for the proper summary.
    // Summaries require an extra space due to negate handling in summary().
    $this->assertEqual($condition->summary(), 'The user is a member of Authenticated user');

    // Set the user role to anonymous.
    $condition->setConfig('roles', array(DRUPAL_ANONYMOUS_RID => DRUPAL_ANONYMOUS_RID));
    $this->assertTrue($condition->execute(), 'Anonymous users pass role checks for anonymous.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The user is a member of Anonymous user');

    // Set the user role to check anonymous or authenticated.
    $condition->setConfig('roles', array(DRUPAL_ANONYMOUS_RID => DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID));
    $this->assertTrue($condition->execute(), 'Anonymous users pass role checks for anonymous or authenticated.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The user is a member of Anonymous user, Authenticated user');

    // Set the context to the authenticated user and check that they also pass
    // against anonymous or authenticated roles.
    $condition->setContextValue('user', $this->authenticated);
    $this->assertTrue($condition->execute(), 'Authenticated users pass role checks for anonymous or authenticated.');

    // Set the role to just authenticated and recheck.
    $condition->setConfig('roles', array(DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID));
    $this->assertTrue($condition->execute(), 'Authenticated users pass role checks for authenticated.');

    // Test Constructor injection.
    $condition = $this->manager->createInstance('user_role', array('roles' => array(DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID), 'context' => array('user' => $this->authenticated)));
    $this->assertTrue($condition->execute(), 'Constructor injection of context and configuration working as anticipated.');

    // Check the negated summary.
    $condition->setConfig('negate', TRUE);
    $this->assertEqual($condition->summary(), 'The user is not a member of Authenticated user');

    // Check the complex negated summary.
    $condition->setConfig('roles', array(DRUPAL_ANONYMOUS_RID => DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID));
    $this->assertEqual($condition->summary(), 'The user is not a member of Anonymous user, Authenticated user');

    // Check a custom role.
    $condition->setConfig('roles', array($this->role->id() => $this->role->id()));
    $condition->setConfig('negate', FALSE);
    $this->assertTrue($condition->execute(), 'Authenticated user is a member of the custom role.');
    $this->assertEqual($condition->summary(), String::format('The user is a member of @roles', array('@roles' => $this->role->label())));
  }

}
