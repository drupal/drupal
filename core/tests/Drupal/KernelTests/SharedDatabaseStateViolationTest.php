<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests how "KernelTestBase" reports shared database state violations.
 */
#[CoversClass(KernelTestBase::class)]
#[Group('PHPUnit')]
#[Group('Test')]
#[Group('KernelTests')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class SharedDatabaseStateViolationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * The name of a table belonging to the initial state.
   */
  private const string PROBE_TABLE = 'shared_state_violation_probe';

  /**
   * A table that a test method adds and does not remove.
   */
  private const string ADDED_TABLE = 'shared_state_violation_added';

  /**
   * Whether ::resetEnvironment() throws.
   */
  private bool $failReset = FALSE;

  /**
   * The schema of the probe table.
   */
  private const array PROBE_SCHEMA = [
    'fields' => [
      'id' => ['type' => 'int', 'not null' => TRUE],
    ],
    'primary key' => ['id'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
    $connection = $this->container->get('database');
    $connection->schema()->createTable(self::PROBE_TABLE, self::PROBE_SCHEMA);
    $connection->insert(self::PROBE_TABLE)->fields(['id' => 1])->execute();
  }

  /**
   * Tests that dropping a table of the initial state is reported.
   */
  public function testDroppedTableIsReported(): void {
    $connection = $this->container->get('database');
    $connection->schema()->dropTable(self::PROBE_TABLE);

    $caught = NULL;
    try {
      $this->checkNoSharedTableWasDropped();
    }
    catch (\RuntimeException $e) {
      $caught = $e;
    }

    $this->assertNotNull($caught, 'Dropping a table of the initial state is reported.');
    $this->assertStringContainsString(self::PROBE_TABLE, $caught->getMessage());
    $this->assertStringContainsString('belongs to the shared environment', $caught->getMessage());

    // Putting the table back is enough to let the check in ::tearDown() pass.
    $connection->schema()->createTable(self::PROBE_TABLE, self::PROBE_SCHEMA);
    $connection->insert(self::PROBE_TABLE)->fields(['id' => 1])->execute();
  }

  /**
   * Tests that a table the test method added is dropped and reported.
   */
  #[Depends('testDroppedTableIsReported')]
  public function testAddedTableIsReported(): void {
    $connection = $this->container->get('database');
    $connection->schema()->createTable(self::ADDED_TABLE, self::PROBE_SCHEMA);

    $caught = NULL;
    try {
      $this->checkNoTableWasLeftBehind();
    }
    catch (\RuntimeException $e) {
      $caught = $e;
    }

    // The table is kept for inspection, so removing it here is enough to let
    // the check in ::tearDown() pass.
    $this->assertTrue($connection->schema()->tableExists(self::ADDED_TABLE), 'The table is kept.');
    $connection->schema()->dropTable(self::ADDED_TABLE);

    $this->assertNotNull($caught, 'A table the test method added is reported.');
    $this->assertStringContainsString(self::ADDED_TABLE, $caught->getMessage());
    $this->assertStringContainsString('must remove what it added', $caught->getMessage());
  }

  /**
   * Tests that a cleanup failing is recorded.
   */
  #[Depends('testAddedTableIsReported')]
  public function testResetFailureIsRecorded(): void {
    $connection = $this->container->get('database');
    $original = $connection->select(self::STATE_TABLE, 's')
      ->fields('s', ['status', 'message', 'tables'])
      ->execute()
      ->fetchAssoc();

    // What ::tearDown() runs. ::resetEnvironment() throws below, so this
    // records the reason and rethrows.
    $this->failReset = TRUE;
    $caught = NULL;
    try {
      $this->tearDownEnvironment();
    }
    catch (\RuntimeException $e) {
      $caught = $e;
    }
    $this->failReset = FALSE;

    $record = $connection->select(self::STATE_TABLE, 's')
      ->fields('s', ['status', 'message'])
      ->execute()
      ->fetchAssoc();

    // Put the record back, otherwise the following test methods would refuse
    // to run, which is the behavior under test rather than a failure.
    $connection->delete(self::STATE_TABLE)->execute();
    $connection->insert(self::STATE_TABLE)->fields($original)->execute();

    $this->assertNotNull($caught, 'The failure of the cleanup is not swallowed.');
    $this->assertSame('invalid', $record['status']);
    $this->assertStringContainsString('::resetEnvironment() failed', $record['message']);
    $this->assertStringContainsString('The cleanup failed.', $record['message']);
  }

  /**
   * Tests that invalidating the state records the reason durably.
   */
  #[Depends('testResetFailureIsRecorded')]
  public function testInvalidationRecordsTheReason(): void {
    $connection = $this->container->get('database');
    $original = $connection->select(self::STATE_TABLE, 's')
      ->fields('s', ['status', 'message', 'tables'])
      ->execute()
      ->fetchAssoc();

    $this->invalidateDatabaseState('Because of a reason.');

    $record = $connection->select(self::STATE_TABLE, 's')
      ->fields('s', ['status', 'message'])
      ->execute()
      ->fetchAssoc();

    $this->assertSame('invalid', $record['status']);
    $this->assertSame('Because of a reason.', $record['message']);

    // Put the record back, otherwise the following test methods would refuse to
    // run, which is the behavior under test rather than a failure. The list of
    // environment tables has to come back as well, because the test methods
    // that follow compare against it.
    $connection->delete(self::STATE_TABLE)->execute();
    $connection->insert(self::STATE_TABLE)->fields($original)->execute();
  }

  /**
   * Tests that the previous restores of the state table succeeded.
   */
  #[Depends('testInvalidationRecordsTheReason')]
  public function testFinal(): void {
    $connection = $this->container->get('database');
    $record = $connection->select(self::STATE_TABLE, 's')
      ->fields('s', ['status', 'message'])
      ->execute()
      ->fetchAssoc();
    $this->assertSame('ready', $record['status']);
  }

  /**
   * {@inheritdoc}
   */
  protected function resetEnvironment(): void {
    parent::resetEnvironment();

    if ($this->failReset) {
      throw new \RuntimeException('The cleanup failed.');
    }
  }

}
