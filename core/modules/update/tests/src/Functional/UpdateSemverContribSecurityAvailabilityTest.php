<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests Update Status with a security update available for a contrib project.
 *
 * @group update
 */
class UpdateSemverContribSecurityAvailabilityTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
