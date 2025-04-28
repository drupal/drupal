<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests Update Status with a security update available for Drupal core.
 *
 * @group update
 */
class UpdateSemverCoreSecurityAvailabilityTest extends UpdateSemverCoreTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
