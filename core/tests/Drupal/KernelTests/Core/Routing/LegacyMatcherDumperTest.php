<?php

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\MatcherDumper;
use Drupal\Core\State\State;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\LoggerInterface;

/**
 * Tests deprecations in MatcherDumper.
 *
 * @group Routing
 * @group legacy
 * @coversDefaultClass \Drupal\Core\Routing\MatcherDumper
 */
class LegacyMatcherDumperTest extends KernelTestBase {

  /**
   * The connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected State $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    $this->connection = $this->createMock(Connection::class);
    $this->state = $this->createMock(State::class);
  }

  /**
   * Tests the constructor deprecations.
   */
  public function testConstructorDeprecationNoLogger() {
    $this->expectDeprecation('Calling Drupal\Core\Routing\MatcherDumper::__construct() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520');
    $dumper = new MatcherDumper($this->connection, $this->state);
    $this->assertNotNull($dumper);
  }

  /**
   * Tests the constructor deprecations.
   */
  public function testConstructorDeprecationWithLegacyTableNameParam() {
    $this->expectDeprecation('Calling Drupal\Core\Routing\MatcherDumper::__construct() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520');
    $dumper = new MatcherDumper($this->connection, $this->state, 'foo');
    $this->assertNotNull($dumper);
  }

  /**
   * Tests the constructor deprecations.
   */
  public function testConstructorDeprecationWithLogger() {
    $logger = $this->createMock(LoggerInterface::class);
    $dumper = new MatcherDumper($this->connection, $this->state, $logger);
    $this->assertNotNull($dumper);

    $logger = $this->createMock(LoggerInterface::class);
    $dumper = new MatcherDumper($this->connection, $this->state, $logger, 'foo');
    $this->assertNotNull($dumper);
  }

}
