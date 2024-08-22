<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests Update Manager with a security update available for a contrib project.
 *
 * @group update
 */
class UpdateSemverContribSecurityAvailabilityTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
