<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\TestTools\Attribute\ShareEnvironment;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the shared database state of Drupal\KernelTests\KernelTestBase.
 *
 * Every test method runs in its own process, so the assertions here cross the
 * process boundary.
 */
#[CoversClass(KernelTestBase::class)]
#[Group('PHPUnit')]
#[Group('Test')]
#[Group('KernelTests')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class SharedDatabaseStateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * A table belonging to the initial state.
   */
  private const string PROBE_TABLE = 'shared_database_state_probe';

  /**
   * A table that a test method creates and ::resetEnvironment() removes.
   */
  private const string CREATED_TABLE = 'shared_database_state_created';

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    $this->installEntitySchema('user');
    $this->installConfig(['system']);

    $this->container->get('database')->schema()->createTable(self::PROBE_TABLE, [
      'fields' => [
        'id' => ['type' => 'serial', 'not null' => TRUE],
        'name' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
      ],
      'primary key' => ['id'],
    ]);
    $this->insertProbe('initial');
  }

  /**
   * {@inheritdoc}
   */
  protected function resetEnvironment(): void {
    parent::resetEnvironment();

    // ::testStateIsWritable() creates this table. A test method may add to the
    // database schema, and removes what it added here.
    $schema = $this->container->get('database')->schema();
    if ($schema->tableExists(self::CREATED_TABLE)) {
      $schema->dropTable(self::CREATED_TABLE);
    }
  }

  /**
   * Initial test method.
   */
  public function testFirst(): void {
    // The probe table exists and holds exactly what ::setUpEnvironment() put
    // in it. Had ::setUpEnvironment() run a second time, creating the table
    // again would have failed.
    $this->assertSame([['id' => '1', 'name' => 'initial']], $this->readProbes());
  }

  /**
   * Tests that the state built once per class is visible to a test method.
   */
  #[Depends('testFirst')]
  public function testInitialState(): void {
    // Default configuration installed by ::setUpEnvironment() is readable.
    $this->assertNotEmpty($this->config('system.date')->get('timezone'));

    // The installed entity type definitions outlived the process that wrote
    // them, so the entity storage is usable.
    $this->assertSame([], User::loadMultiple());
  }

  /**
   * Tests that a test method can change the shared state.
   */
  #[Depends('testInitialState')]
  public function testStateIsWritable(): void {
    $connection = $this->container->get('database');
    $this->insertProbe('second');
    $connection->update(self::PROBE_TABLE)
      ->fields(['name' => 'changed'])
      ->condition('id', 1)
      ->execute();

    User::create(['name' => 'shared_state_user'])->save();

    $connection->schema()->createTable(self::CREATED_TABLE, [
      'fields' => ['id' => ['type' => 'int', 'not null' => TRUE]],
    ]);

    $this->assertSame([
      ['id' => '1', 'name' => 'changed'],
      ['id' => '2', 'name' => 'second'],
    ], $this->readProbes());
    $this->assertCount(1, User::loadMultiple());
  }

  /**
   * Tests that nothing the previous test method did was undone.
   */
  #[Depends('testStateIsWritable')]
  public function testNothingWasRestored(): void {
    // The inserted row is still there and the updated one is still updated.
    $this->assertSame([
      ['id' => '1', 'name' => 'changed'],
      ['id' => '2', 'name' => 'second'],
    ], $this->readProbes());

    // So is the entity the previous test method created.
    $this->assertCount(1, User::loadMultiple());

    // The table that the previous test method created is gone, because
    // ::resetEnvironment() removed it. Rows survive, schema does not.
    $this->assertFalse($this->container->get('database')->schema()->tableExists(self::CREATED_TABLE));

    // The serial ID kept advancing, because nothing rewound it.
    $this->insertProbe('third');
    $this->assertSame([
      ['id' => '1', 'name' => 'changed'],
      ['id' => '2', 'name' => 'second'],
      ['id' => '3', 'name' => 'third'],
    ], $this->readProbes());
  }

  /**
   * Inserts a row into the probe table.
   *
   * @param string $name
   *   The name to store.
   */
  private function insertProbe(string $name): void {
    $this->container->get('database')->insert(self::PROBE_TABLE)
      ->fields(['name' => $name])
      ->execute();
  }

  /**
   * Reads the probe table.
   *
   * @return list<array<string, string>>
   *   The rows, ordered by identifier.
   */
  private function readProbes(): array {
    $rows = [];
    $result = $this->container->get('database')->select(self::PROBE_TABLE, 'p')
      ->fields('p', ['id', 'name'])
      ->orderBy('id')
      ->execute();
    foreach ($result as $row) {
      $rows[] = ['id' => (string) $row->id, 'name' => $row->name];
    }
    return $rows;
  }

}
