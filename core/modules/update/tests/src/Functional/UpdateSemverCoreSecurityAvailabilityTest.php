<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Update Status with a security update available for Drupal core.
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class UpdateSemverCoreSecurityAvailabilityTest extends UpdateSemverCoreTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
