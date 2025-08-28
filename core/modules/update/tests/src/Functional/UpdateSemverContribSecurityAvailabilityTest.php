<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Update Status with a security update available for a contrib project.
 */
#[Group('update')]
class UpdateSemverContribSecurityAvailabilityTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
