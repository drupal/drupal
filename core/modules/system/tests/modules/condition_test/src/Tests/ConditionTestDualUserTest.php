<?php

/**
 * @file
 * Contains \Drupal\condition_test\Tests\ConditionTestDualUserTest.
 */

namespace Drupal\condition_test\Tests;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests a condition that requires two users.
 *
 * @group condition_test
 */
class ConditionTestDualUserTest extends KernelTestBase {

  /**
   * An anonymous user for testing purposes.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $anonymous;

  /**
   * An authenticated user for testing purposes.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authenticated;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'condition_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');

    $this->anonymous = User::create(['uid' => 0]);
    $this->authenticated = User::create(['uid' => 1]);
  }

  /**
   * Tests the dual user condition.
   */
  public function testConditions() {
    $this->doTestIdenticalUser();
    $this->doTestDifferentUser();
  }

  /**
   * Tests with both contexts mapped to the same user.
   */
  protected function doTestIdenticalUser() {
    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    $condition = \Drupal::service('plugin.manager.condition')
      ->createInstance('condition_test_dual_user')
      // Map the anonymous user to both contexts.
      ->setContextMapping([
        'user1' => 'anonymous',
        'user2' => 'anonymous',
      ]);
    $definition = new ContextDefinition('entity:user');
    $contexts['anonymous'] = new Context($definition, $this->anonymous);
    \Drupal::service('context.handler')->applyContextMapping($condition, $contexts);
    $this->assertTrue($condition->execute());
  }

  /**
   * Tests with each context mapped to different users.
   */
  protected function doTestDifferentUser() {
    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    $condition = \Drupal::service('plugin.manager.condition')
      ->createInstance('condition_test_dual_user')
      ->setContextMapping([
        'user1' => 'anonymous',
        'user2' => 'authenticated',
      ]);
    $definition = new ContextDefinition('entity:user');
    $contexts['anonymous'] = new Context($definition, $this->anonymous);
    $contexts['authenticated'] = new Context($definition, $this->authenticated);
    \Drupal::service('context.handler')->applyContextMapping($condition, $contexts);
    $this->assertFalse($condition->execute());
  }

}
