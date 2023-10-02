<?php

namespace Drupal\Tests\update\Functional;

/**
 * Tests Update Manager with a security update available for Drupal core.
 *
 * @group update
 * @group #slow
 */
class UpdateSemverCoreSecurityAvailabilityTest extends UpdateSemverCoreTestBase {

  use UpdateSemverTestSecurityAvailabilityTrait;

}
