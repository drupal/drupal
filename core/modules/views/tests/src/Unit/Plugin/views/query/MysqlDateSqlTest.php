<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\query;

use Drupal\Core\Database\Connection;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\MysqlDateSql;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MySQL-specific date query handler.
 */
#[CoversClass(MysqlDateSql::class)]
#[Group('views')]
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
   */
  public function testGetDateField(): void {
    $date_sql = new MysqlDateSql($this->database);

    $expected = 'foo.field';
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', TRUE));

    $expected = "DATE_ADD('19700101', INTERVAL foo.field SECOND)";
    $this->assertEquals($expected, $date_sql->getDateField('foo.field', FALSE));
  }

  /**
   * Tests date formatting replacement.
   */
  #[DataProvider('providerTestGetDateFormat')]
  public function testGetDateFormat($field, $format, $expected_format): void {
    $date_sql = new MysqlDateSql($this->database);

    $this->assertEquals("DATE_FORMAT($field, '$expected_format')", $date_sql->getDateFormat($field, $format));
  }

  /**
   * Provider for date formatting test.
   */
  public static function providerTestGetDateFormat() {
    return [
      ['foo.field', 'Y-y-M-m', '%Y-%y-%b-%m'],
      ['bar.field', 'n-F D d l', '%c-%M %a %d %W'],
      ['baz.bar_field', 'o j/W/H-h i s A', '%x %e/%v/%H-%h %i %s %p'],
    ];
  }

  /**
   * Tests timezone offset formatting.
   */
  public function testSetFieldTimezoneOffset(): void {
    $date_sql = new MysqlDateSql($this->database);

    $field = 'foobar.field';
    $date_sql->setFieldTimezoneOffset($field, 42);
    $this->assertEquals("(foobar.field + INTERVAL 42 SECOND)", $field);
  }

  /**
   * Tests setting the database offset.
   */
  public function testSetTimezoneOffset(): void {
    $database = $this->prophesize(Connection::class);
    $database->query("SET @@session.time_zone = '42'")->shouldBeCalledTimes(1);
    $date_sql = new MysqlDateSql($database->reveal());
    $date_sql->setTimezoneOffset(42);
  }

}
