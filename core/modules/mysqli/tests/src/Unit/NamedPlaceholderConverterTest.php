<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Unit;

use Drupal\mysqli\Driver\Database\mysqli\NamedPlaceholderConverter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\mysqli\Driver\Database\mysqli\NamedPlaceholderConverter.
 */
#[CoversClass(NamedPlaceholderConverter::class)]
#[Group('Database')]
class NamedPlaceholderConverterTest extends UnitTestCase {

  /**
   * Tests ::parse().
   *
   * @legacy-covers ::parse
   * @legacy-covers ::getConvertedSQL
   * @legacy-covers ::getConvertedParameters
   */
  #[DataProvider('statementsWithParametersProvider')]
  public function testParse(string $sql, array $parameters, string $expectedSql, array $expectedParameters): void {
    $converter = new NamedPlaceholderConverter();
    $converter->parse($sql, $parameters);
    $this->assertSame($expectedSql, $converter->getConvertedSQL());
    $this->assertSame($expectedParameters, $converter->getConvertedParameters());
  }

  /**
   * Data for testParse.
   */
  public static function statementsWithParametersProvider(): iterable {
    yield [
      'SELECT ?',
      ['foo'],
      'SELECT ?',
      ['foo'],
    ];

    yield [
      'SELECT * FROM Foo WHERE bar IN (?, ?, ?)',
      ['baz', 'qux', 'fred'],
      'SELECT * FROM Foo WHERE bar IN (?, ?, ?)',
      ['baz', 'qux', 'fred'],
    ];

    yield [
      'SELECT ? FROM ?',
      ['baz', 'qux'],
      'SELECT ? FROM ?',
      ['baz', 'qux'],
    ];

    yield [
      'SELECT "?" FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT "?" FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      "SELECT '?' FROM foo WHERE bar = ?",
      ['baz'],
      "SELECT '?' FROM foo WHERE bar = ?",
      ['baz'],
    ];

    yield [
      'SELECT `?` FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT `?` FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      'SELECT [?] FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT [?] FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, ARRAY[?])',
      ['baz'],
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, ARRAY[?])',
      ['baz'],
    ];

    yield [
      "SELECT 'foo-bar?' FROM foo WHERE bar = ?",
      ['baz'],
      "SELECT 'foo-bar?' FROM foo WHERE bar = ?",
      ['baz'],
    ];

    yield [
      'SELECT "foo-bar?" FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT "foo-bar?" FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      'SELECT `foo-bar?` FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT `foo-bar?` FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      'SELECT [foo-bar?] FROM foo WHERE bar = ?',
      ['baz'],
      'SELECT [foo-bar?] FROM foo WHERE bar = ?',
      ['baz'],
    ];

    yield [
      'SELECT :foo FROM :bar',
      [':foo' => 'baz', ':bar' => 'qux'],
      'SELECT ? FROM ?',
      ['baz', 'qux'],
    ];

    yield [
      'SELECT * FROM Foo WHERE bar IN (:name1, :name2)',
      [':name1' => 'baz', ':name2' => 'qux'],
      'SELECT * FROM Foo WHERE bar IN (?, ?)',
      ['baz', 'qux'],
    ];

    yield [
      'SELECT ":foo" FROM Foo WHERE bar IN (:name1, :name2)',
      [':name1' => 'baz', ':name2' => 'qux'],
      'SELECT ":foo" FROM Foo WHERE bar IN (?, ?)',
      ['baz', 'qux'],
    ];

    yield [
      "SELECT ':foo' FROM Foo WHERE bar IN (:name1, :name2)",
      [':name1' => 'baz', ':name2' => 'qux'],
      "SELECT ':foo' FROM Foo WHERE bar IN (?, ?)",
      ['baz', 'qux'],
    ];

    yield [
      'SELECT :foo_id',
      [':foo_id' => 'bar'],
      'SELECT ?',
      ['bar'],
    ];

    yield [
      'SELECT @rank := 1 AS rank, :foo AS foo FROM :bar',
      [':foo' => 'baz', ':bar' => 'qux'],
      'SELECT @rank := 1 AS rank, ? AS foo FROM ?',
      ['baz', 'qux'],
    ];

    yield [
      'SELECT * FROM Foo WHERE bar > :start_date AND baz > :start_date',
      [':start_date' => 'qux'],
      'SELECT * FROM Foo WHERE bar > ? AND baz > ?',
      ['qux', 'qux'],
    ];

    yield [
      'SELECT foo::date as date FROM Foo WHERE bar > :start_date AND baz > :start_date',
      [':start_date' => 'qux'],
      'SELECT foo::date as date FROM Foo WHERE bar > ? AND baz > ?',
      ['qux', 'qux'],
    ];

    yield [
      'SELECT `d.ns:col_name` FROM my_table d WHERE `d.date` >= :param1',
      [':param1' => 'qux'],
      'SELECT `d.ns:col_name` FROM my_table d WHERE `d.date` >= ?',
      ['qux'],
    ];

    yield [
      'SELECT [d.ns:col_name] FROM my_table d WHERE [d.date] >= :param1',
      [':param1' => 'qux'],
      'SELECT [d.ns:col_name] FROM my_table d WHERE [d.date] >= ?',
      ['qux'],
    ];

    yield [
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, ARRAY[:foo])',
      [':foo' => 'qux'],
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, ARRAY[?])',
      ['qux'],
    ];

    yield [
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, array[:foo])',
      [':foo' => 'qux'],
      'SELECT * FROM foo WHERE jsonb_exists_any(foo.bar, array[?])',
      ['qux'],
    ];

    yield [
      "SELECT table.column1, ARRAY['3'] FROM schema.table table WHERE table.f1 = :foo AND ARRAY['3']",
      [':foo' => 'qux'],
      "SELECT table.column1, ARRAY['3'] FROM schema.table table WHERE table.f1 = ? AND ARRAY['3']",
      ['qux'],
    ];

    yield [
      "SELECT table.column1, ARRAY['3']::integer[] FROM schema.table table WHERE table.f1 = :foo AND ARRAY['3']::integer[]",
      [':foo' => 'qux'],
      "SELECT table.column1, ARRAY['3']::integer[] FROM schema.table table WHERE table.f1 = ? AND ARRAY['3']::integer[]",
      ['qux'],
    ];

    yield [
      "SELECT table.column1, ARRAY[:foo] FROM schema.table table WHERE table.f1 = :bar AND ARRAY['3']",
      [':foo' => 'qux', ':bar' => 'git'],
      "SELECT table.column1, ARRAY[?] FROM schema.table table WHERE table.f1 = ? AND ARRAY['3']",
      ['qux', 'git'],
    ];

    yield [
      'SELECT table.column1, ARRAY[:foo]::integer[] FROM schema.table table' . " WHERE table.f1 = :bar AND ARRAY['3']::integer[]",
      [':foo' => 'qux', ':bar' => 'git'],
      'SELECT table.column1, ARRAY[?]::integer[] FROM schema.table table' . " WHERE table.f1 = ? AND ARRAY['3']::integer[]",
      ['qux', 'git'],
    ];

    yield 'Parameter array with placeholder keys missing starting colon' => [
      'SELECT table.column1, ARRAY[:foo]::integer[] FROM schema.table table' . " WHERE table.f1 = :bar AND ARRAY['3']::integer[]",
      ['foo' => 'qux', 'bar' => 'git'],
      'SELECT table.column1, ARRAY[?]::integer[] FROM schema.table table' . " WHERE table.f1 = ? AND ARRAY['3']::integer[]",
      ['qux', 'git'],
    ];

    yield 'Quotes inside literals escaped by doubling' => [
      <<<'SQL'
SELECT * FROM foo
WHERE bar = ':not_a_param1 ''":not_a_param2"'''
   OR bar=:a_param1
   OR bar=:a_param2||':not_a_param3'
   OR bar=':not_a_param4 '':not_a_param5'' :not_a_param6'
   OR bar=''
   OR bar=:a_param3
SQL,
      [':a_param1' => 'qux', ':a_param2' => 'git', ':a_param3' => 'foo'],
    <<<'SQL'
SELECT * FROM foo
WHERE bar = ':not_a_param1 ''":not_a_param2"'''
   OR bar=?
   OR bar=?||':not_a_param3'
   OR bar=':not_a_param4 '':not_a_param5'' :not_a_param6'
   OR bar=''
   OR bar=?
SQL,
      ['qux', 'git', 'foo'],
    ];

    yield [
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE :condition_0 ESCAPE \'\\\\\') AND (data.description LIKE :condition_1 ESCAPE \'\\\\\') ORDER BY id ASC',
      [':condition_0' => 'qux', ':condition_1' => 'git'],
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE ? ESCAPE \'\\\\\') AND (data.description LIKE ? ESCAPE \'\\\\\') ORDER BY id ASC',
      ['qux', 'git'],
    ];

    yield [
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE :condition_0 ESCAPE "\\\\") AND (data.description LIKE :condition_1 ESCAPE "\\\\") ORDER BY id ASC',
      [':condition_0' => 'qux', ':condition_1' => 'git'],
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE ? ESCAPE "\\\\") AND (data.description LIKE ? ESCAPE "\\\\") ORDER BY id ASC',
      ['qux', 'git'],
    ];

    yield 'Combined single and double quotes' => [
      <<<'SQL'
SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id
  FROM test_data data
 WHERE (data.description LIKE :condition_0 ESCAPE "\\")
   AND (data.description LIKE :condition_1 ESCAPE '\\') ORDER BY id ASC
SQL,
      [':condition_0' => 'qux', ':condition_1' => 'git'],
      <<<'SQL'
SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id
  FROM test_data data
 WHERE (data.description LIKE ? ESCAPE "\\")
   AND (data.description LIKE ? ESCAPE '\\') ORDER BY id ASC
SQL,
      ['qux', 'git'],
    ];

    yield [
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE :condition_0 ESCAPE `\\\\`) AND (data.description LIKE :condition_1 ESCAPE `\\\\`) ORDER BY id ASC',
      [':condition_0' => 'qux', ':condition_1' => 'git'],
      'SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id FROM test_data data WHERE (data.description LIKE ? ESCAPE `\\\\`) AND (data.description LIKE ? ESCAPE `\\\\`) ORDER BY id ASC',
      ['qux', 'git'],
    ];

    yield 'Combined single quotes and backticks' => [
      <<<'SQL'
SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id
  FROM test_data data
 WHERE (data.description LIKE :condition_0 ESCAPE '\\')
   AND (data.description LIKE :condition_1 ESCAPE `\\`) ORDER BY id ASC
SQL,
      [':condition_0' => 'qux', ':condition_1' => 'git'],
      <<<'SQL'
SELECT data.age AS age, data.id AS id, data.name AS name, data.id AS id
  FROM test_data data
 WHERE (data.description LIKE ? ESCAPE '\\')
   AND (data.description LIKE ? ESCAPE `\\`) ORDER BY id ASC
SQL,
      ['qux', 'git'],
    ];

    yield '? placeholders inside comments' => [
      <<<'SQL'
/*
 * test placeholder ?
 */
SELECT dummy as "dummy?"
  FROM DUAL
 WHERE '?' = '?'
-- AND dummy <> ?
   AND dummy = ?
SQL,
      ['baz'],
      <<<'SQL'
/*
 * test placeholder ?
 */
SELECT dummy as "dummy?"
  FROM DUAL
 WHERE '?' = '?'
-- AND dummy <> ?
   AND dummy = ?
SQL,
      ['baz'],
    ];

    yield 'Named placeholders inside comments' => [
      <<<'SQL'
/*
 * test :placeholder
 */
SELECT dummy as "dummy?"
  FROM DUAL
 WHERE '?' = '?'
-- AND dummy <> :dummy
   AND dummy = :key
SQL,
      [':key' => 'baz'],
      <<<'SQL'
/*
 * test :placeholder
 */
SELECT dummy as "dummy?"
  FROM DUAL
 WHERE '?' = '?'
-- AND dummy <> :dummy
   AND dummy = ?
SQL,
      ['baz'],
    ];

    yield 'Escaped question' => [
      <<<'SQL'
SELECT '{"a":null}'::jsonb ?? :key
SQL,
      [':key' => 'qux'],
      <<<'SQL'
SELECT '{"a":null}'::jsonb ?? ?
SQL,
      ['qux'],
    ];
  }

  /**
   * Tests reusing the parser object.
   *
   * @legacy-covers ::parse
   * @legacy-covers ::getConvertedSQL
   * @legacy-covers ::getConvertedParameters
   */
  public function testParseReuseObject(): void {
    $converter = new NamedPlaceholderConverter();
    $converter->parse('SELECT ?', ['foo']);
    $this->assertSame('SELECT ?', $converter->getConvertedSQL());
    $this->assertSame(['foo'], $converter->getConvertedParameters());

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Missing Positional Parameter 0');
    $converter->parse('SELECT ?', []);
  }

}
