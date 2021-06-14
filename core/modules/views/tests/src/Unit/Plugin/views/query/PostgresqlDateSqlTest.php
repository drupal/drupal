<?php

namespace Drupal\Tests\views\Unit\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\PostgresqlDateSql;

/**
 * Tests the PostgreSQL-specific date query handler.
 *
 * @coversDefaultClass \Drupal\views\Plugin\views\query\PostgresqlDateSql
 *
 * @group views
 */
class PostgresqlDateSqlTest extends UnitTestCase {

  /**
   * The mocked database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->database = $this->prophesize(Connection::class)->reveal();
  }

  /**
   * Tests the getDateField method.
   *
   * @covers ::getDateField
   */
  public function testGetDateField() {
    $date_sql = new PostgresqlDateSql($this->database);

    $expected = "TO_TIMESTAMP(foo.field, 'YYYY-MM-DD\"T\"HH24:MI:SS')";
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', TRUE));

    $expected = 'TO_TIMESTAMP(foo.field)';
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', FALSE));
  }

  /**
   * Tests date formatting replacement.
   *
   * @covers ::getDateFormat
   *
   * @dataProvider providerTestGetDateFormat
   */
  public function testGetDateFormat($field, $format, $expected_format) {
    $date_sql = new PostgresqlDateSql($this->database);

    $this->assertEquals("TO_CHAR($field, '$expected_format')", $date_sql->getDateFormat($field, $format));
  }

  /**
   * Provider for date formatting test.
   */
  public function providerTestGetDateFormat() {
    return [
      ['foo.field', 'Y-y-M-m', 'YYYY-YY-Mon-MM'],
      ['bar.field', 'n-F D d l', 'MM-Month Dy DD Day'],
      ['baz.bar_field', 'j/W/H-h i s A', 'DD/IW/HH24-HH12 MI SS AM'],
    ];
  }

  /**
   * Tests timezone offset formatting.
   *
   * @covers ::setFieldTimezoneOffset
   */
  public function testSetFieldTimezoneOffset() {
    $date_sql = new PostgresqlDateSql($this->database);

    $field = 'foobar.field';
    $date_sql->setFieldTimezoneOffset($field, 42);
    $this->assertEquals("(foobar.field + INTERVAL '42 SECONDS')", $field);
  }

  /**
   * Tests setting the database offset.
   *
   * @covers ::setTimezoneOffset
   */
  public function testSetTimezoneOffset() {
    $database = $this->prophesize(Connection::class);
    $database->query("SET TIME ZONE INTERVAL '42' HOUR TO MINUTE")->shouldBeCalledTimes(1);
    $date_sql = new PostgresqlDateSql($database->reveal());
    $date_sql->setTimezoneOffset(42);
  }

}
