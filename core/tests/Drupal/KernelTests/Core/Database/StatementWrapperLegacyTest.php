<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\StatementWrapper;

/**
 * Tests the deprecations of the StatementWrapper class.
 *
 * @coversDefaultClass \Drupal\Core\Database\StatementWrapper
 * @group legacy
 * @group Database
 */
class StatementWrapperLegacyTest extends DatabaseTestBase {

  protected $statement;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->statement = $this->connection->prepareStatement('SELECT id FROM {test}', []);
    if (!$this->statement instanceof StatementWrapper) {
      $this->markTestSkipped('This test only works for drivers implementing Drupal\Core\Database\StatementWrapper.');
    }
  }

  /**
   * @covers ::getQueryString
   */
  public function testQueryString() {
    $this->expectDeprecation('StatementWrapper::$queryString should not be accessed in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488');
    $this->assertStringContainsString('SELECT id FROM ', $this->statement->queryString);
    $this->assertStringContainsString('SELECT id FROM ', $this->statement->getQueryString());
  }

  /**
   * Tests calling a non existing \PDOStatement method.
   */
  public function testMissingMethod() {
    $this->expectException('\BadMethodCallException');
    $this->statement->boo();
  }

  /**
   * Tests calling an existing \PDOStatement method.
   */
  public function testClientStatementMethod() {
    $this->expectDeprecation('StatementWrapper::columnCount should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488');
    $this->statement->execute();
    $this->assertEquals(1, $this->statement->columnCount());
  }

  /**
   * @covers ::bindParam
   */
  public function testBindParam() {
    $this->expectDeprecation('StatementWrapper::bindParam should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488');
    $test = NULL;
    $this->assertTrue($this->statement->bindParam(':name', $test));
  }

  /**
   * @covers ::bindColumn
   */
  public function testBindColumn() {
    $this->expectDeprecation('StatementWrapper::bindColumn should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488');
    $test = NULL;
    $this->assertTrue($this->statement->bindColumn(1, $test));
  }

}
