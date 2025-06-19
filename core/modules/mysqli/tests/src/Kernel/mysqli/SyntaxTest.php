<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\KernelTests\Core\Database\DriverSpecificSyntaxTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests MySql syntax interpretation.
 */
#[Group('Database')]
class SyntaxTest extends DriverSpecificSyntaxTestBase {

  /**
   * Tests string concatenation with separator, with field values.
   */
  public function testConcatWsFields(): void {
    $result = $this->connection->query("SELECT CONCAT_WS('-', CONVERT(:a1 USING utf8mb4), [name], CONVERT(:a2 USING utf8mb4), [age]) FROM {test} WHERE [age] = :age", [
      ':a1' => 'name',
      ':a2' => 'age',
      ':age' => 25,
    ]);
    $this->assertSame('name-John-age-25', $result->fetchField());
  }

}
