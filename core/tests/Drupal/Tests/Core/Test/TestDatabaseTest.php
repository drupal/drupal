<?php

namespace Drupal\Tests\Core\Test;

use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Test\TestDatabase
 * @group Template
 */
class TestDatabaseTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructorException() {
    $this->setExpectedException(\InvalidArgumentException::class, "Invalid database prefix: blah1253");
    new TestDatabase('blah1253');
  }

  /**
   * @covers ::__construct
   * @covers ::getDatabasePrefix
   * @covers ::getTestSitePath
   *
   * @dataProvider providerTestConstructor
   */
  public function testConstructor($db_prefix, $expected_db_prefix, $expected_site_path) {
    $test_db = new TestDatabase($db_prefix);
    $this->assertEquals($expected_db_prefix, $test_db->getDatabasePrefix());
    $this->assertEquals($expected_site_path, $test_db->getTestSitePath());
  }

  /**
   * Data provider for self::testConstructor()
   */
  public function providerTestConstructor() {
    return [
      ['test1234', 'test1234', 'sites/simpletest/1234'],
      ['test123456test234567', 'test123456test234567', 'sites/simpletest/234567']
    ];
  }

}
