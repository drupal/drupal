<?php

namespace Drupal\Tests\views\Unit\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\SqliteDateSql;

/**
 * Tests the MySQL-specific date query handler.
 *
 * @coversDefaultClass \Drupal\views\Plugin\views\query\SqliteDateSql
 *
 * @group views
 */
class SqliteDateSqlTest extends UnitTestCase {

  /**
   * The mocked database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->prophesize(Connection::class)->reveal();
  }

  /**
   * Tests the getDateField method.
   *
   * @covers ::getDateField
   */
  public function testGetDateField() {
    $date_sql = new SqliteDateSql($this->database);

    $expected = "strftime('%s', foo.field)";
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', TRUE));

    $expected = 'foo.field';
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', FALSE));
  }

  /**
   * Tests date formatting replacement.
   *
   * @covers ::getDateFormat
   *
   * @dataProvider providerTestGetDateFormat
   */
  public function testGetDateFormat($field, $format, $expected) {
    $date_sql = new SqliteDateSql($this->database);

    $this->assertEquals($expected, $date_sql->getDateFormat($field, $format));
  }

  /**
   * Provider for date formatting test.
   */
  public function providerTestGetDateFormat() {
    return [
      ['foo.field', 'Y-y-M-m', "strftime('%Y-%Y-%m-%m', foo.field, 'unixepoch')"],
      ['bar.field', 'n-F D d l', "strftime('%m-%m %d %d %d', bar.field, 'unixepoch')"],
      ['baz.bar_field', 'j/W/H-h i s A', "strftime('%d/%W/%H-%H %M %S ', baz.bar_field, 'unixepoch')"],
      ['foo.field', 'W', "CAST(((strftime('%j', date(strftime('%Y-%m-%d', foo.field, 'unixepoch'), '-3 days', 'weekday 4')) - 1) / 7 + 1) AS NUMERIC)"],
    ];
  }

  /**
   * Tests timezone offset formatting.
   *
   * @covers ::setFieldTimezoneOffset
   */
  public function testSetFieldTimezoneOffset() {
    $date_sql = new SqliteDateSql($this->database);

    $field = 'foobar.field';
    $date_sql->setFieldTimezoneOffset($field, 42);
    $this->assertEquals("(foobar.field + 42)", $field);
  }

  /**
   * Tests setting the database offset.
   *
   * @covers ::setTimezoneOffset
   */
  public function testSetTimezoneOffset() {
    $database = $this->prophesize(Connection::class);
    $database->query()->shouldNotBeCalled();
    $date_sql = new SqliteDateSql($database->reveal());
    $date_sql->setTimezoneOffset(42);
  }

}
