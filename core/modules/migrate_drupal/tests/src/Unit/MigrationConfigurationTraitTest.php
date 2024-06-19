<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Unit;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate_drupal\MigrationConfigurationTrait
 * @group migrate_drupal
 */
class MigrationConfigurationTraitTest extends UnitTestCase {

  /**
   * @covers ::getLegacyDrupalVersion
   * @dataProvider providerTestGetLegacyDrupalVersion
   */
  public function testGetLegacyDrupalVersion($expected_version_string, $schema_version, $exception, $system_table_exists): void {
    if ($schema_version) {
      $statement = $this->createMock('\Drupal\Core\Database\StatementInterface');
      $statement->expects($this->any())
        ->method('fetchField')
        ->willReturn($schema_version);
    }

    $schema = $this->createMock('\Drupal\Core\Database\Schema');
    $schema->expects($this->once())
      ->method('tableExists')
      ->willReturn($system_table_exists);

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    if ($exception) {
      $connection->expects($this->any())
        ->method('query')
        ->willThrowException($exception);
    }
    else {
      $connection->expects($this->any())
        ->method('query')
        ->willReturn($statement);
    }

    $connection->expects($this->any())
      ->method('schema')
      ->willReturn($schema);

    $actual_version_string = TestMigrationConfigurationTrait::getLegacyDrupalVersion($connection);
    $this->assertSame($expected_version_string, $actual_version_string);
  }

  /**
   * Provides data for testGetLegacyDrupalVersion.
   */
  public static function providerTestGetLegacyDrupalVersion() {
    return [
      'D5' => [
        'expected_version_string' => '5',
        'schema_version' => '1678',
        'exception' => NULL,
        'system_table_exists' => TRUE,
      ],
      'D6' => [
        'expected_version_string' => '6',
        'schema_version' => '6057',
        'exception' => NULL,
        'system_table_exists' => TRUE,
      ],
      'D7' => [
        'expected_version_string' => '7',
        'schema_version' => '7065',
        'exception' => NULL,
        'system_table_exists' => TRUE,
      ],
      'D8' => [
        'expected_version_string' => FALSE,
        'schema_version' => serialize('8976'),
        'exception' => NULL,
        'system_table_exists' => FALSE,
      ],
      'D9' => [
        'expected_version_string' => FALSE,
        'schema_version' => serialize('9270'),
        'exception' => NULL,
        'system_table_exists' => FALSE,
      ],
      'D10' => [
        'expected_version_string' => FALSE,
        'schema_version' => serialize('10101'),
        'exception' => NULL,
        'system_table_exists' => FALSE,
      ],
      'Not drupal' => [
        'expected_version_string' => FALSE,
        'schema_version' => "not drupal I guess",
        'exception' => NULL,
        'system_table_exists' => FALSE,
      ],
      'D5 almost' => [
        'expected_version_string' => FALSE,
        'schema_version' => '123',
        'exception' => NULL,
        'system_table_exists' => TRUE,
      ],
      'D5/6/7 Exception' => [
        'expected_version_string' => FALSE,
        'schema_version' => NULL,
        'exception' => new DatabaseExceptionWrapper(),
        'system_table_exists' => TRUE,
      ],
      'D8/9 Exception' => [
        'expected_version_string' => FALSE,
        'schema_version' => NULL,
        'exception' => new DatabaseExceptionWrapper(),
        'system_table_exists' => FALSE,
      ],
    ];
  }

}

/**
 * Test class that uses the trait we are testing.
 */
class TestMigrationConfigurationTrait {
  use MigrationConfigurationTrait;

}
