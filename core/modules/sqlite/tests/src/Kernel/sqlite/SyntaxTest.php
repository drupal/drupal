<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\KernelTests\Core\Database\DriverSpecificSyntaxTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests SQLite syntax interpretation.
 */
#[Group('Database')]
class SyntaxTest extends DriverSpecificSyntaxTestBase {
}
