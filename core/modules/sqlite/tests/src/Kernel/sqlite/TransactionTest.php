<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\KernelTests\Core\Database\DriverSpecificTransactionTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests transaction for the SQLite driver.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class TransactionTest extends DriverSpecificTransactionTestBase {
}
