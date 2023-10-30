<?php

namespace Drupal\system_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A controller that does not specify its autowired dependencies correctly.
 */
class BrokenSystemTestController extends ControllerBase {

  /**
   * Constructs the BrokenSystemTestController.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   */
  public function __construct(
    protected LockBackendInterface $lock,
  ) {}

}
