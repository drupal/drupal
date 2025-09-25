<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\KernelTests\Core\Database\DriverSpecificSyntaxTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests MySql syntax interpretation.
 */
#[Group('Database')]
class SyntaxTest extends DriverSpecificSyntaxTestBase {
}
