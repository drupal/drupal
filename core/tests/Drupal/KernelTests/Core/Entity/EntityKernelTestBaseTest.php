<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Entity Kernel Test Base.
 */
#[Group('Entity')]
#[CoversClass(EntityKernelTestBase::class)]
#[RunTestsInSeparateProcesses]
class EntityKernelTestBaseTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createUser();
  }

  /**
   * Tests that the current user is set up correctly.
   */
  public function testSetUpCurrentUser(): void {
    $account = $this->setUpCurrentUser();
    $current_user = \Drupal::currentUser();
    $this->assertSame($account->id(), $current_user->id());
  }

}
