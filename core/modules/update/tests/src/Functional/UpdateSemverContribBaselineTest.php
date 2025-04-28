<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests the Update Status module with a contrib module with semver versions.
 *
 * @group update
 */
class UpdateSemverContribBaselineTest extends UpdateSemverContribTestBase {

  use UpdateSemverTestBaselineTrait;

}
