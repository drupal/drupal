<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificTransactionTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests transaction for the PostgreSQL driver.
 */
#[Group('Database')]
class TransactionTest extends DriverSpecificTransactionTestBase {
}
