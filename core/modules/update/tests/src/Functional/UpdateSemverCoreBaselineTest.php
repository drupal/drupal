<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests semantic version handling in the Update Status module for Drupal core.
 */
#[Group('update')]
class UpdateSemverCoreBaselineTest extends UpdateSemverCoreTestBase {
  use UpdateSemverTestBaselineTrait;

}
