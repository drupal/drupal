<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateTestCase.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Database\Driver\fake\FakeConnection;

/**
 * Provides setup and helper methods for Migrate module tests.
 */
abstract class MigrateTestCase extends UnitTestCase {

  protected $migrationConfiguration = array();

  /**
   * Retrieve a mocked migration.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked migration.
   */
  protected function getMigration() {
    $this->migrationConfiguration += ['migrationClass' => 'Drupal\migrate\Entity\Migration'];
    $this->idMap = $this->getMock('Drupal\migrate\Plugin\MigrateIdMapInterface');

    $this->idMap->expects($this->any())
      ->method('getQualifiedMapTableName')
      ->will($this->returnValue('test_map'));

    $migration = $this->getMockBuilder($this->migrationConfiguration['migrationClass'])
      ->disableOriginalConstructor()
      ->getMock();
    $migration->expects($this->any())
      ->method('checkRequirements')
      ->will($this->returnValue(TRUE));
    $migration->expects($this->any())
      ->method('getIdMap')
      ->will($this->returnValue($this->idMap));
    $configuration = &$this->migrationConfiguration;
    $migration->expects($this->any())->method('get')->will($this->returnCallback(function ($argument) use (&$configuration) {
      return isset($configuration[$argument]) ? $configuration[$argument] : '';
    }));
    $migration->expects($this->any())->method('set')->will($this->returnCallback(function ($argument, $value) use (&$configuration) {
      $configuration[$argument] = $value;
    }));
    $migration->expects($this->any())
      ->method('id')
      ->will($this->returnValue($configuration['id']));
    return $migration;
  }

  /**
   * Get a fake database connection object for use in tests.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows, an associative array of field => value.
   * @param array $connection_options
   *   (optional) The array of connection options for the database.
   * @param string $prefix
   *   (optional) The table prefix on the database.
   *
   * @return \Drupal\Core\Database\Driver\fake\FakeConnection
   *   The database connection.
   */
  protected function getDatabase(array $database_contents, $connection_options = array(), $prefix = '') {
    return new FakeConnection($database_contents, $connection_options, $prefix);
  }

  /**
   * Tests a query
   *
   * @param array|\Traversable
   *   The countable. foreach-able actual results if a query is being run.
   */
  public function queryResultTest($iter, $expected_results) {
    $this->assertSame(count($expected_results), count($iter), 'Number of results match');
    $count = 0;
    foreach ($iter as $data_row) {
      $expected_row = $expected_results[$count];
      $count++;
      foreach ($expected_row as $key => $expected_value) {
        $this->retrievalAssertHelper($expected_value, $this->getValue($data_row, $key), sprintf('Value matches for key "%s"', $key));
      }
    }
    $this->assertSame(count($expected_results), $count);
  }

  /**
   * @param array $row
   * @param string $key
   * @return mixed
   */
  protected function getValue($row, $key) {
    return $row[$key];
  }

  /**
   * Asserts tested values during test retrieval.
   *
   * @param mixed $expected_value
   *   The incoming expected value to test.
   * @param mixed $actual_value
   *   The incoming value itself.
   * @param string $message
   *   The tested result as a formatted string.
   */
  protected function retrievalAssertHelper($expected_value, $actual_value, $message) {
    if (is_array($expected_value)) {
      foreach ($expected_value as $k => $v) {
        $this->retrievalAssertHelper($v, $actual_value[$k], $message . '[' . $k . ']');
      }
    }
    else {
      $this->assertSame((string) $expected_value, (string) $actual_value, $message);
    }
  }

}
