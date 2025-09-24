<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Update Status with a security update available for a contrib project.
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class UpdateSemverContribSecurityAvailabilityTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
