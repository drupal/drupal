<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;

/**
 * Provides a base class for user role action tests.
 */
abstract class RoleUserTestBase extends UnitTestCase {

  /**
   * The mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * The user role entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userRoleEntityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this
      ->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $this->userRoleEntityType = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
  }

}
