<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\KernelTests\Core\Database\SchemaUniquePrefixedKeysIndexTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests adding UNIQUE keys to tables.
 */
#[Group('Database')]
class SchemaUniquePrefixedKeysIndexTest extends SchemaUniquePrefixedKeysIndexTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $columnValue = '1234567890 foo';

}
