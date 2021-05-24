<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Kernel\Scripts\DbCommandBaseTest.
 */

namespace Drupal\Tests\system\Kernel\Scripts;

use Drupal\Core\Command\DbCommandBase;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test that the DbToolsApplication works correctly.
 *
 * The way console application's run it is impossible to test. For now we only
 * test that we are registering the correct commands.
 *
 * @group console
 */
class DbCommandBaseTest extends KernelTestBase {

  /**
   * Tests specifying a database key.
   */
  public function testSpecifyDatabaseKey() {
    $command = new DbCommandBaseTester();
    $command_tester = new CommandTester($command);

    Database::addConnectionInfo('magic_db', 'default', Database::getConnectionInfo('default')['default']);

    $command_tester->execute([
      '--database' => 'magic_db',
    ]);
    $this->assertEquals('magic_db', $command->getDatabaseConnection($command_tester->getInput())->getKey(),
       'Special db key is returned');
  }

  /**
   * Invalid database names will throw a useful exception.
   */
  public function testSpecifyDatabaseDoesNotExist() {
    $command = new DbCommandBaseTester();
    $command_tester = new CommandTester($command);
    $command_tester->execute([
      '--database' => 'dne',
    ]);
    $this->expectException(ConnectionNotDefinedException::class);
    $command->getDatabaseConnection($command_tester->getInput());
  }

  /**
   * Tests supplying database connection as a url.
   */
  public function testSpecifyDbUrl() {
    $command = new DbCommandBaseTester();
    $command_tester = new CommandTester($command);
    $command_tester->execute([
      '-db-url' => Database::getConnectionInfoAsUrl(),
    ]);
    $this->assertEquals('db-tools', $command->getDatabaseConnection($command_tester->getInput())->getKey());

    Database::removeConnection('db-tools');
    $command_tester->execute([
      '--database-url' => Database::getConnectionInfoAsUrl(),
    ]);
    $this->assertEquals('db-tools', $command->getDatabaseConnection($command_tester->getInput())->getKey());
  }

  /**
   * Tests specifying a prefix for different connections.
   */
  public function testPrefix() {
    if (Database::getConnection()->driver() == 'sqlite') {
      $this->markTestSkipped('SQLITE modifies the prefixes so we cannot effectively test it');
    }

    Database::addConnectionInfo('magic_db', 'default', Database::getConnectionInfo('default')['default']);
    $command = new DbCommandBaseTester();
    $command_tester = new CommandTester($command);
    $command_tester->execute([
      '--database' => 'magic_db',
      '--prefix' => 'extra',
    ]);
    $this->assertEquals('extra', $command->getDatabaseConnection($command_tester->getInput())->tablePrefix());

    $command_tester->execute([
      '-db-url' => Database::getConnectionInfoAsUrl(),
      '--prefix' => 'extra2',
    ]);
    $this->assertEquals('extra2', $command->getDatabaseConnection($command_tester->getInput())->tablePrefix());

    // This breaks simpletest cleanup.
    //    $command_tester->execute([
    //      '--prefix' => 'notsimpletest',
    //    ]);
    //    $this->assertEquals('notsimpletest', $command->getDatabaseConnection($command_tester->getInput())->tablePrefix());
  }

}

/**
 * Concrete command implementation for testing base features.
 */
class DbCommandBaseTester extends DbCommandBase {

  /**
   * {@inheritdoc}
   */
  public function configure() {
    parent::configure();
    $this->setName('test');
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabaseConnection(InputInterface $input) {
    return parent::getDatabaseConnection($input);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Empty implementation for testing.
    return 0;
  }

}
