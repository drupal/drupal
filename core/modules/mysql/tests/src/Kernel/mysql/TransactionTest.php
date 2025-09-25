<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\KernelTests\Core\Database\DriverSpecificTransactionTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests transaction for the MySQL driver.
 */
#[Group('Database')]
class TransactionTest extends DriverSpecificTransactionTestBase {
}
