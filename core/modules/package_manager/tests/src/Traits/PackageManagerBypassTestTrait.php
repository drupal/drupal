<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;

/**
 * Common functions for testing using the package_manager_bypass module.
 *
 * @internal
 */
trait PackageManagerBypassTestTrait {

  /**
   * Asserts the number of times an update was staged.
   *
   * @param int $attempted_times
   *   The expected number of times an update was staged.
   */
  protected function assertUpdateStagedTimes(int $attempted_times): void {
    /** @var \Drupal\package_manager_bypass\LoggingBeginner $beginner */
    $beginner = $this->container->get(BeginnerInterface::class);
    $this->assertCount($attempted_times, $beginner->getInvocationArguments());

    /** @var \Drupal\package_manager_bypass\NoOpStager $stager */
    $stager = $this->container->get(StagerInterface::class);
    // If an update was attempted, then there will be at least two calls to the
    // stager: one to change the runtime constraints in composer.json, and
    // another to actually update the installed dependencies. If any dev
    // packages (like `drupal/core-dev`) are installed, there may also be an
    // additional call to change the dev constraints.
    $this->assertGreaterThanOrEqual($attempted_times * 2, count($stager->getInvocationArguments()));

    /** @var \Drupal\package_manager_bypass\LoggingCommitter $committer */
    $committer = $this->container->get(CommitterInterface::class);
    $this->assertEmpty($committer->getInvocationArguments());
  }

}
