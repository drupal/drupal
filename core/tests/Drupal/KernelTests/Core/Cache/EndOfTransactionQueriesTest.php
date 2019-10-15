<?php

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\DatabaseBackendFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests that cache tag invalidation queries are delayed to the end of transactions.
 *
 * @group Cache
 */
class EndOfTransactionQueriesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'delay_cache_tags_invalidation',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!class_exists($this->getDatabaseConnectionInfo()['default']['namespace'] . '\Connection')) {
      $this->markTestSkipped(sprintf('No logging override exists for the %s database driver. Create it, subclass this test class and override ::getDatabaseConnectionInfo().', $this->getDatabaseConnectionInfo()['default']['driver']));
    }

    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    // Ensure the cachetags table already exists.
    Cache::invalidateTags([$this->randomString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Register a database cache backend rather than memory-based.
    $container->register('cache_factory', DatabaseBackendFactory::class)
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('cache_tags.invalidator.checksum'))
      ->addArgument(new Reference('settings'));
  }

  /**
   * {@inheritdoc}
   */
  public function testEntitySave() {
    \Drupal::cache()->set('test_cache_pretransaction_foobar', 'something', Cache::PERMANENT, ['foobar']);
    \Drupal::cache()->set('test_cache_pretransaction_entity_test_list', 'something', Cache::PERMANENT, ['entity_test_list']);

    $entity = EntityTest::create(['name' => $this->randomString()]);
    \Drupal::database()->resetLoggedStatements();

    $entity->save();

    $executed_statements = \Drupal::database()->getLoggedStatements();
    $last_statement_index = max(array_keys($executed_statements));

    $cachetag_statements = array_keys($this->getStatementsForTable(\Drupal::database()->getLoggedStatements(), 'cachetags'));
    $this->assertSame($last_statement_index - count($cachetag_statements) + 1, min($cachetag_statements), 'All of the last queries in the transaction are for the "cachetags" table.');

    // Verify that a nested entity save occurred.
    $this->assertSame('johndoe', User::load(1)->getAccountName());

    // Cache reads occurring during a transaction that DO NOT depend on
    // invalidated cache tags result in cache HITs. Similarly, cache writes that
    // DO NOT depend on invalidated cache tags DO get written. Of course, if we
    // read either one now, outside of the context of the transaction, we expect
    // the same.
    $this->assertNotEmpty(\Drupal::state()->get('delay_cache_tags_invalidation_entity_test_insert__pretransaction_foobar'));
    $this->assertNotEmpty(\Drupal::cache()->get('delay_cache_tags_invalidation_entity_test_insert__during_transaction_foobar'));
    $this->assertNotEmpty(\Drupal::state()->get('delay_cache_tags_invalidation_user_insert__during_transaction_foobar'));
    $this->assertNotEmpty(\Drupal::cache()->get('test_cache_pretransaction_foobar'));

    // Cache reads occurring during a transaction that DO depend on invalidated
    // cache tags result in cache MISSes. Similarly, cache writes that DO depend
    // on invalidated cache tags DO NOT get written. Of course, if we read
    // either one now, outside of the context of the transaction, we expect the
    // same.
    $this->assertFalse(\Drupal::state()->get('delay_cache_tags_invalidation_entity_test_insert__pretransaction_entity_test_list'));
    $this->assertFalse(\Drupal::cache()->get('delay_cache_tags_invalidation_entity_test_insert__during_transaction_entity_test_list'));
    $this->assertFalse(\Drupal::state()->get('delay_cache_tags_invalidation_user_insert__during_transaction_entity_test_list'));
    $this->assertFalse(\Drupal::cache()->get('test_cache_pretransaction_entity_test_list'));
  }

  /**
   * {@inheritdoc}
   */
  public function testEntitySaveRollback() {
    \Drupal::cache()
      ->set('test_cache_pretransaction_entity_test_list', 'something', Cache::PERMANENT, ['entity_test_list']);
    \Drupal::cache()
      ->set('test_cache_pretransaction_user_list', 'something', Cache::PERMANENT, ['user_list']);

    \Drupal::state()->set('delay_cache_tags_invalidation_exception', TRUE);

    try {
      EntityTest::create(['name' => $this->randomString()])->save();
      $this->fail('Exception not thrown');
    }
    catch (\Exception $e) {
      $this->assertEquals('Abort entity save to trigger transaction rollback.', $e->getMessage());
    }

    // The cache has not been invalidated.
    $this->assertNotEmpty(\Drupal::cache()->get('test_cache_pretransaction_entity_test_list'));
    $this->assertNotEmpty(\Drupal::cache()->get('test_cache_pretransaction_user_list'));

    // Save a user, that should invalidate the cache tagged with user_list but
    // not the one with entity_test_list.
    User::create([
      'name' => 'johndoe',
      'status' => 1,
    ])->save();

    $this->assertNotEmpty(\Drupal::cache()->get('test_cache_pretransaction_entity_test_list'));
    $this->assertFalse(\Drupal::cache()->get('test_cache_pretransaction_user_list'));
  }

  /**
   * Filters statements by table name.
   *
   * @param string[] $statements
   *   A list of query statements.
   * @param $table_name
   *   The name of the table to filter by.
   *
   * @return string[]
   *   Filtered statement list.
   */
  protected function getStatementsForTable(array $statements, $table_name) {
    $tables = array_filter(array_map([$this, 'statementToTableName'], $statements));
    return array_filter($tables, function ($table_for_statement) use ($table_name) {
      return $table_for_statement === $table_name;
    });
  }

  /**
   * Returns the table name for a statement.
   *
   * @param string $statement
   *   The query statement.
   *
   * @return string|null
   *   The name of the table or NULL if none was found.
   */
  protected static function statementToTableName($statement) {
    if (preg_match('/.*\{([^\}]+)\}.*/', $statement, $matches)) {
      return $matches[1];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDatabaseConnectionInfo() {
    $info = parent::getDatabaseConnectionInfo();
    // Override default database driver to one that does logging. Third-party
    // (non-core) database drivers can achieve the same test coverage by
    // subclassing this test class and overriding only this method.
    // @see \Drupal\database_statement_monitoring_test\LoggedStatementsTrait
    // @see \Drupal\database_statement_monitoring_test\mysql\Connection
    // @see \Drupal\database_statement_monitoring_test\pgsql\Connection
    // @see \Drupal\database_statement_monitoring_test\sqlite\Connection
    $info['default']['namespace'] = '\Drupal\database_statement_monitoring_test\\' . $info['default']['driver'];
    return $info;
  }

}
