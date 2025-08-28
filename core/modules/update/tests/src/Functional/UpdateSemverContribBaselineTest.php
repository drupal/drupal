<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Update Status module with a contrib module with semver versions.
 */
#[Group('update')]
class UpdateSemverContribBaselineTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestBaselineTrait;

}
