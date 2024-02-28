<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests semantic version handling in the Update Manager for Drupal core.
 *
 * @group update
 * @group #slow
 */
class UpdateSemverCoreBaselineTest extends UpdateSemverCoreTestBase {
  use UpdateSemverTestBaselineTrait;

}
