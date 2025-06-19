<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\Tests\mysql\Kernel\mysql\PrefixInfoTest as BaseMySqlTest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the prefix info for a database schema is correct.
 */
#[Group('Database')]
class PrefixInfoTest extends BaseMySqlTest {
}
