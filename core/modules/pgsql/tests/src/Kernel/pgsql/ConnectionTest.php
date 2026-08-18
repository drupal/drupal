<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore héllo wörld PGCLIENTENCODING libpq

/**
 * PostgreSQL-specific connection tests.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class ConnectionTest extends DriverSpecificKernelTestBase {

  /**
   * Tests the client encoding is set to UTF8 when the connection is opened.
   *
   * The client encoding is set via the DSN in
   * \Drupal\pgsql\Driver\Database\pgsql\Connection::open(), so it is part of
   * the connection startup packet and no "SET NAMES" query is needed.
   */
  public function testClientEncoding(): void {
    $this->assertSame('UTF8', $this->connection->query('SHOW client_encoding')->fetchField());

    // Ensure multi-byte UTF-8 data round trips intact.
    $string = 'héllo wörld ✓ 漢字';
    $this->assertSame($string, $this->connection->query('SELECT :string::text', [':string' => $string])->fetchField());

    // The client encoding in the DSN must take precedence over the
    // PGCLIENTENCODING environment variable, which libpq would otherwise use
    // as the default.
    putenv('PGCLIENTENCODING=LATIN1');
    try {
      Database::addConnectionInfo('encoding_test', 'default', Database::getConnectionInfo()['default']);
      $connection = Database::getConnection('default', 'encoding_test');
      $this->assertSame('UTF8', $connection->query('SHOW client_encoding')->fetchField());
    }
    finally {
      putenv('PGCLIENTENCODING');
      Database::closeConnection('default', 'encoding_test');
    }
  }

}
