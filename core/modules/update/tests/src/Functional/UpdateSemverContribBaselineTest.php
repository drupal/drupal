<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests the Update Manager module with a contrib module with semver versions.
 *
 * @group update
 * @group #slow
 */
class UpdateSemverContribBaselineTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestBaselineTrait;

}
