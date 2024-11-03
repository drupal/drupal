<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\DatabaseBackendFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\Reference;
use Drupal\Component\Serialization\PhpSerialize;

// cspell:ignore pretransaction

/**
 * Tests delaying of cache tag invalidation queries to the end of transactions.
 *
 * @group Cache
 */
class EndOfTransactionQueriesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'delay_cache_tags_invalidation',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    // Ensure the cachetags table already exists.
    Cache::invalidateTags([$this->randomString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->register('serializer', PhpSerialize::class);
    // Register a database cache backend rather than memory-based.
    $container->register('cache_factory', DatabaseBackendFactory::class)
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('cache_tags.invalidator.checksum'))
      ->addArgument(new Reference('settings'))
      ->addArgument(new Reference('serializer'))
      ->addArgument(new Reference(TimeInterface::class));
  }

  /**
   * Tests an entity save.
   */
  public function testEntitySave(): void {
    \Drupal::cache()->set('test_cache_pretransaction_foobar', 'something', Cache::PERMANENT, ['foobar']);
    \Drupal::cache()->set('test_cache_pretransaction_entity_test_list', 'something', Cache::PERMANENT, ['entity_test_list']);

    $entity = EntityTest::create(['name' => $this->randomString()]);

    Database::startLog('testEntitySave');
    $entity->save();

    $executed_statements = [];
    foreach (Database::getLog('testEntitySave') as $log) {
      // Exclude transaction related statements from the log.
      if (
        str_starts_with($log['query'], 'ROLLBACK TO SAVEPOINT ') ||
        str_starts_with($log['query'], 'RELEASE SAVEPOINT ') ||
        str_starts_with($log['query'], 'SAVEPOINT ')
      ) {
        continue;
      }
      $executed_statements[] = $log['query'];
    }
    $last_statement_index = max(array_keys($executed_statements));
    $cachetag_statements = array_keys($this->getStatementsForTable($executed_statements, 'cachetags'));
    $this->assertSame($last_statement_index - count($cachetag_statements) + 1, min($cachetag_statements), 'All of the last queries in the transaction are for the "cachetags" table.');

    // Verify that a nested entity save occurred.
    $this->assertSame('john doe', User::load(1)->getAccountName());

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
   * Tests an entity save rollback.
   */
  public function testEntitySaveRollback(): void {
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
      'name' => 'john doe',
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
   * @param string $table_name
   *   The name of the table to filter by.
   *
   * @return string[]
   *   Filtered statement list.
   */
  protected function getStatementsForTable(array $statements, $table_name): array {
    return array_filter($statements, function ($statement) use ($table_name) {
      return $this->isStatementRelatedToTable($statement, $table_name);
    });
  }

  /**
   * Determines if a statement is relative to a specified table.
   *
   * Non-core database drivers can override this method if they have different
   * patterns to identify table related statements.
   *
   * @param string $statement
   *   The query statement.
   * @param string $tableName
   *   The table name, Drupal style, without curly brackets or prefix.
   *
   * @return bool
   *   TRUE if the statement is relative to the table, FALSE otherwise.
   */
  protected static function isStatementRelatedToTable(string $statement, string $tableName): bool {
    $realTableIdentifier = Database::getConnection()->prefixTables('{' . $tableName . '}');
    $pattern = '/.*(INTO|FROM|UPDATE)( |\n)' . preg_quote($realTableIdentifier, '/') . '/';
    return preg_match($pattern, $statement) === 1 ? TRUE : FALSE;
  }

}
