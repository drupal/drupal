<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\Tests\mysql\Kernel\mysql\TemporaryQueryTest as BaseMySqlTest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the temporary query functionality.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class TemporaryQueryTest extends BaseMySqlTest {
}
