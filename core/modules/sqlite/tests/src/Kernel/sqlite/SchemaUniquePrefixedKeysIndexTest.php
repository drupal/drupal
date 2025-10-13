<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\KernelTests\Core\Database\SchemaUniquePrefixedKeysIndexTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests adding UNIQUE keys to tables.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SchemaUniquePrefixedKeysIndexTest extends SchemaUniquePrefixedKeysIndexTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $columnValue = '1234567890 foo';

}
