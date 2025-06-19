<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\Tests\mysql\Kernel\mysql\SchemaTest as BaseMySqlTest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests schema API for the MySQL driver.
 */
#[Group('Database')]
class SchemaTest extends BaseMySqlTest {
}
