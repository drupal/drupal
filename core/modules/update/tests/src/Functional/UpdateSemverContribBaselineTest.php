<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Update Status module with a contrib module with semver versions.
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class UpdateSemverContribBaselineTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestBaselineTrait;

}
