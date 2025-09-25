<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\KernelTests\Core\Database\DriverSpecificTransactionTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests transaction for the SQLite driver.
 */
#[Group('Database')]
class TransactionTest extends DriverSpecificTransactionTestBase {
}
