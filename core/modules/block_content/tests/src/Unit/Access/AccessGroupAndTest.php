<?php

namespace Drupal\Tests\block_content\Unit\Access;

use Drupal\block_content\Access\AccessGroupAnd;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests accessible groups.
 *
 * @group block_content
 */
class AccessGroupAndTest extends UnitTestCase {

  use AccessibleTestingTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->account = $this->prophesize(AccountInterface::class)->reveal();
  }

  /**
   * @covers \Drupal\block_content\Access\AccessGroupAnd
   */
  public function testGroups() {
    $allowedAccessible = $this->createAccessibleDouble(AccessResult::allowed());
    $forbiddenAccessible = $this->createAccessibleDouble(AccessResult::forbidden());
    $neutralAccessible = $this->createAccessibleDouble(AccessResult::neutral());

    // Ensure that groups with no dependencies return a neutral access result.
    $this->assertTrue((new AccessGroupAnd())->access('view', $this->account, TRUE)->isNeutral());

    $andNeutral = new AccessGroupAnd();
    $andNeutral->addDependency($allowedAccessible)->addDependency($neutralAccessible);
    $this->assertTrue($andNeutral->access('view', $this->account, TRUE)->isNeutral());

    $andForbidden = $andNeutral;
    $andForbidden->addDependency($forbiddenAccessible);
    $this->assertTrue($andForbidden->access('view', $this->account, TRUE)->isForbidden());

    // Ensure that groups added to other groups works.
    $andGroupsForbidden = new AccessGroupAnd();
    $andGroupsForbidden->addDependency($andNeutral)->addDependency($andForbidden);
    $this->assertTrue($andGroupsForbidden->access('view', $this->account, TRUE)->isForbidden());
    // Ensure you can add a non-group accessible object.
    $andGroupsForbidden->addDependency($allowedAccessible);
    $this->assertTrue($andGroupsForbidden->access('view', $this->account, TRUE)->isForbidden());
  }

}
