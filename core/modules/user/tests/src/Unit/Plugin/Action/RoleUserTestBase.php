<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\Action;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Provides a base class for user role action tests.
 */
abstract class RoleUserTestBase extends UnitTestCase {

  /**
   * The mocked account.
   */
  protected UserInterface&MockObject $account;

  /**
   * The user role entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\Stub
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
    $this->userRoleEntityType = $this->createStub(EntityTypeInterface::class);
  }

}
