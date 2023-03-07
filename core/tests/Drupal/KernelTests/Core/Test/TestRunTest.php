<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\JUnitConverter;
use Drupal\Core\Test\PhpUnitTestRunner;
use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\SimpletestTestRunResultsStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Test\TestRun
 * @group Test
 */
class TestRunTest extends KernelTestBase {

  /**
   * The database connection for testing.
   *
   * NOTE: this is the connection to the fixture database to allow testing the
   * storage class, NOT the database where actual tests results are stored.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The test run results storage.
   *
   * @var \Drupal\Core\Test\TestRunResultsStorageInterface
   */
  protected $testRunResultsStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->connection = Database::getConnection();
    $this->testRunResultsStorage = new SimpletestTestRunResultsStorage($this->connection);
    $this->testRunResultsStorage->buildTestingResultsEnvironment(FALSE);
  }

  /**
   * @covers ::createNew
   * @covers ::get
   * @covers ::id
   * @covers ::insertLogEntry
   * @covers ::setDatabasePrefix
   * @covers ::getDatabasePrefix
   * @covers ::getTestClass
   */
  public function testCreateAndGet(): void {
    // Test ::createNew.
    $test_run = TestRun::createNew($this->testRunResultsStorage);
    $this->assertEquals(1, $test_run->id());
    $this->assertEquals(0, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    $test_run->setDatabasePrefix('oddity1234');
    $this->assertEquals('oddity1234', $test_run->getDatabasePrefix());
    $this->assertEquals('oddity1234', $this->connection->select('simpletest_test_id', 's')->fields('s', ['last_prefix'])->execute()->fetchField());

    $this->assertEquals(1, $test_run->insertLogEntry($this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals('oddity1234', $test_run->getDatabasePrefix());
    $this->assertEquals('Test\GroundControl', $test_run->getTestClass());
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    // Explicitly void the $test_run variable.
    $test_run = NULL;

    // Test ::get.
    $test_run = TestRun::get($this->testRunResultsStorage, 1);
    $this->assertEquals(1, $test_run->id());
    $this->assertEquals('oddity1234', $test_run->getDatabasePrefix());
    $this->assertEquals('Test\GroundControl', $test_run->getTestClass());
  }

  /**
   * @covers ::createNew
   * @covers ::id
   * @covers ::insertLogEntry
   * @covers ::setDatabasePrefix
   */
  public function testCreateAndRemove(): void {
    $test_run_1 = TestRun::createNew($this->testRunResultsStorage);
    $test_run_1->setDatabasePrefix('oddity1234');
    $test_run_1->insertLogEntry($this->getTestLogEntry('Test\GroundControl'));
    $this->assertEquals(1, $test_run_1->id());
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    $test_run_2 = TestRun::createNew($this->testRunResultsStorage);
    $test_run_2->setDatabasePrefix('oddity5678');
    $test_run_2->insertLogEntry($this->getTestLogEntry('Test\PlanetEarth'));
    $this->assertEquals(2, $test_run_2->id());
    $this->assertEquals(2, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(2, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());

    $this->assertEquals(1, $test_run_1->removeResults());
    $this->assertEquals(1, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
    $this->assertEquals(1, $this->connection->select('simpletest_test_id')->countQuery()->execute()->fetchField());
  }

  /**
   * @covers ::createNew
   * @covers ::insertLogEntry
   * @covers ::setDatabasePrefix
   * @covers ::getLogEntriesByTestClass
   * @covers ::getDatabasePrefix
   * @covers ::getTestClass
   */
  public function testGetLogEntriesByTestClass(): void {
    $test_run = TestRun::createNew($this->testRunResultsStorage);
    $test_run->setDatabasePrefix('oddity1234');
    $this->assertEquals(1, $test_run->insertLogEntry($this->getTestLogEntry('Test\PlanetEarth')));
    $this->assertEquals(2, $test_run->insertLogEntry($this->getTestLogEntry('Test\GroundControl')));
    $this->assertEquals([
      0 => (object) [
        'message_id' => 2,
        'test_id' => 1,
        'test_class' => 'Test\GroundControl',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => 0,
        'file' => 'Unknown',
      ],
      1 => (object) [
        'message_id' => 1,
        'test_id' => 1,
        'test_class' => 'Test\PlanetEarth',
        'status' => 'pass',
        'message' => 'Major Tom',
        'message_group' => 'other',
        'function' => 'Unknown',
        'line' => 0,
        'file' => 'Unknown',
      ],
    ], $test_run->getLogEntriesByTestClass());
    $this->assertEquals('oddity1234', $test_run->getDatabasePrefix());
    $this->assertEquals('Test\GroundControl', $test_run->getTestClass());
  }

  /**
   * @covers ::createNew
   * @covers ::setDatabasePrefix
   * @covers ::processPhpErrorLogFile
   * @covers ::getLogEntriesByTestClass
   */
  public function testProcessPhpErrorLogFile(): void {
    $test_run = TestRun::createNew($this->testRunResultsStorage);
    $test_run->setDatabasePrefix('oddity1234');
    $test_run->processPhpErrorLogFile('core/tests/fixtures/test-error.log', 'Test\PlanetEarth');
    $this->assertEquals([
      0 => (object) [
        'message_id' => '1',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "Argument 1 passed to Drupal\FunctionalTests\Bootstrap\ErrorContainer::Drupal\FunctionalTests\Bootstrap\{closure}() must be an instance of Drupal\FunctionalTests\Bootstrap\ErrorContainer, int given, called",
        'message_group' => 'TypeError',
        'function' => 'Unknown',
        'line' => '18',
        'file' => '/var/www/core/tests/Drupal/FunctionalTests/Bootstrap/ErrorContainer.php on line 20 in /var/www/core/tests/Drupal/FunctionalTests/Bootstrap/ErrorContainer.php',
      ],
      1 => (object) [
        'message_id' => '2',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#1 /var/www/core/lib/Drupal/Core/DrupalKernel.php(1396): Drupal\FunctionalTests\Bootstrap\ErrorContainer->get('http_kernel')\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      2 => (object) [
        'message_id' => '3',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#2 /var/www/core/lib/Drupal/Core/DrupalKernel.php(693): Drupal\Core\DrupalKernel->getHttpKernel()\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      3 => (object) [
        'message_id' => '4',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#3 /var/www/index.php(19): Drupal\Core\DrupalKernel->handle(Object(Symfony\Component\HttpFoundation\Request))\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      4 => (object) [
        'message_id' => '5',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#4 {main}\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      5 => (object) [
        'message_id' => '6',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "Thrown exception during Container::get",
        'message_group' => 'Exception',
        'function' => 'Unknown',
        'line' => '17',
        'file' => '/var/www/core/tests/Drupal/FunctionalTests/Bootstrap/ExceptionContainer.php',
      ],
      6 => (object) [
        'message_id' => '7',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#1 /var/www/core/lib/Drupal/Core/DrupalKernel.php(693): Drupal\Core\DrupalKernel->getHttpKernel()\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      7 => (object) [
        'message_id' => '8',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#2 /var/www/index.php(19): Drupal\Core\DrupalKernel->handle(Object(Symfony\Component\HttpFoundation\Request))\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
      8 => (object) [
        'message_id' => '9',
        'test_id' => '1',
        'test_class' => 'Test\PlanetEarth',
        'status' => 'fail',
        'message' => "#3 {main}\n",
        'message_group' => 'Fatal error',
        'function' => 'Unknown',
        'line' => '0',
        'file' => 'Unknown',
      ],
    ], $test_run->getLogEntriesByTestClass());
  }

  /**
   * @covers ::insertLogEntry
   */
  public function testProcessPhpUnitResults(): void {
    $phpunit_error_xml = __DIR__ . '/../../../Tests/Core/Test/fixtures/phpunit_error.xml';
    $res = JUnitConverter::xmlToRows(1, $phpunit_error_xml);

    $runner = PhpUnitTestRunner::create(\Drupal::getContainer());
    $test_run = TestRun::createNew($this->testRunResultsStorage);
    $runner->processPhpUnitResults($test_run, $res);

    $this->assertEquals(4, $this->connection->select('simpletest')->countQuery()->execute()->fetchField());
  }

  /**
   * Returns a sample test run log entry.
   *
   * @param string $test_class
   *   The test class.
   *
   * @return string[]
   *   An array with the elements to be logged.
   */
  protected function getTestLogEntry(string $test_class): array {
    return [
      'test_class' => $test_class,
      'status' => 'pass',
      'message' => 'Major Tom',
      'message_group' => 'other',
    ];
  }

}
