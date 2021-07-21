<?php

namespace Drupal\Tests\Core\Test;

use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Test\TestDatabase
 *
 * @group Test
 * @group simpletest
 * @group Template
 */
class TestDatabaseTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructorException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid database prefix: blah1253");
    new TestDatabase('blah1253');
  }

  /**
   * @covers ::__construct
   * @covers ::getDatabasePrefix
   * @covers ::getTestSitePath
   * @covers ::getPhpErrorLogPath
   *
   * @dataProvider providerTestConstructor
   */
  public function testConstructor($db_prefix, $expected_db_prefix, $expected_site_path) {
    $test_db = new TestDatabase($db_prefix);
    $this->assertEquals($expected_db_prefix, $test_db->getDatabasePrefix());
    $this->assertEquals($expected_site_path, $test_db->getTestSitePath());
    $this->assertEquals($expected_site_path . '/error.log', $test_db->getPhpErrorLogPath());
  }

  /**
   * Data provider for self::testConstructor()
   */
  public function providerTestConstructor() {
    return [
      ['test1234', 'test1234', 'sites/simpletest/1234'],
      ['test123456test234567', 'test123456test234567', 'sites/simpletest/234567'],
    ];
  }

  /**
   * Verify that a test lock is generated if there is no provided prefix.
   *
   * @covers ::__construct
   * @covers ::getDatabasePrefix
   * @covers ::getTestSitePath
   * @covers ::getPhpErrorLogPath
   */
  public function testConstructorNullPrefix() {
    // We use a stub class here because we can't mock getTestLock() so that it's
    // available before the constructor is called.
    $test_db = new TestTestDatabase(NULL);

    $this->assertEquals('test23', $test_db->getDatabasePrefix());
    $this->assertEquals('sites/simpletest/23', $test_db->getTestSitePath());
    $this->assertEquals('sites/simpletest/23/error.log', $test_db->getPhpErrorLogPath());
  }

}

/**
 * Stub class supports TestDatabaseTest::testConstructorNullPrefix().
 */
class TestTestDatabase extends TestDatabase {

  protected function getTestLock(bool $create_lock = FALSE): int {
    return 23;
  }

}
