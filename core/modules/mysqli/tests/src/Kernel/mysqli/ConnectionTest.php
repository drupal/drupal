<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\Tests\mysql\Kernel\mysql\ConnectionTest as BaseMySqlTest;
use PHPUnit\Framework\Attributes\Group;

/**
 * MySQL-specific connection tests.
 */
#[Group('Database')]
class ConnectionTest extends BaseMySqlTest {
}
