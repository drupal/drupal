<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Helper methods testing accessible interfaces.
 */
trait AccessibleTestingTrait {

  /**
   * The test account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Creates AccessibleInterface object from access result object for testing.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $accessResult
   *   The accessible result to return.
   *
   * @return \Drupal\Core\Access\AccessibleInterface
   *   The AccessibleInterface object.
   */
  private function createAccessibleDouble(AccessResultInterface $accessResult) {
    $accessible = $this->prophesize(AccessibleInterface::class);
    $accessible->access('view', $this->account, TRUE)
      ->willReturn($accessResult);
    return $accessible->reveal();
  }

}
