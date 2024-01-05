<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\MysqlDateSql;

/**
 * Tests the MySQL-specific date query handler.
 *
 * @coversDefaultClass \Drupal\views\Plugin\views\query\MysqlDateSql
 *
 * @group views
 */
class MysqlDateSqlTest extends UnitTestCase {

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
    $date_sql = new MysqlDateSql($this->database);

    $expected = 'foo.field';
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', TRUE));

    $expected = "DATE_ADD('19700101', INTERVAL foo.field SECOND)";
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
    $date_sql = new MysqlDateSql($this->database);

    $this->assertEquals("DATE_FORMAT($field, '$expected_format')", $date_sql->getDateFormat($field, $format));
  }

  /**
   * Provider for date formatting test.
   */
  public function providerTestGetDateFormat() {
    return [
      ['foo.field', 'Y-y-M-m', '%Y-%y-%b-%m'],
      ['bar.field', 'n-F D d l', '%c-%M %a %d %W'],
      ['baz.bar_field', 'o j/W/H-h i s A', '%x %e/%v/%H-%h %i %s %p'],
    ];
  }

  /**
   * Tests timezone offset formatting.
   *
   * @covers ::setFieldTimezoneOffset
   */
  public function testSetFieldTimezoneOffset() {
    $date_sql = new MysqlDateSql($this->database);

    $field = 'foobar.field';
    $date_sql->setFieldTimezoneOffset($field, 42);
    $this->assertEquals("(foobar.field + INTERVAL 42 SECOND)", $field);
  }

  /**
   * Tests setting the database offset.
   *
   * @covers ::setTimezoneOffset
   */
  public function testSetTimezoneOffset() {
    $database = $this->prophesize(Connection::class);
    $database->query("SET @@session.time_zone = '42'")->shouldBeCalledTimes(1);
    $date_sql = new MysqlDateSql($database->reveal());
    $date_sql->setTimezoneOffset(42);
  }

}
