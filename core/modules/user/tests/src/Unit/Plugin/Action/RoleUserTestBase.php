<?php

namespace Drupal\Tests\user\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;

/**
 * Provides a base class for user role action tests.
 */
abstract class RoleUserTestBase extends UnitTestCase {

  /**
   * The mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The user role entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $userRoleEntityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this
      ->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $this->userRoleEntityType = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
  }

}
