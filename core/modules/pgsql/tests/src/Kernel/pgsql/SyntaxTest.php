<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificSyntaxTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests PostgreSQL syntax interpretation.
 */
#[Group('Database')]
class SyntaxTest extends DriverSpecificSyntaxTestBase {
}
