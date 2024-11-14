<?php

declare(strict_types=1);

namespace Drupal\delay_cache_tags_invalidation\Hook;

use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Cache\Cache;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for delay_cache_tags_invalidation.
 */
class DelayCacheTagsInvalidationHooks {

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('entity_test_insert')]
  public function entityTestInsert(EntityTest $entity) {
    if (\Drupal::state()->get('delay_cache_tags_invalidation_exception')) {
      throw new \Exception('Abort entity save to trigger transaction rollback.');
    }
    // Read the pre-transaction cache writes.
    // @see \Drupal\KernelTests\Core\Cache\EndOfTransactionQueriesTest::testEntitySave()
    \Drupal::state()->set('delay_cache_tags_invalidation_entity_test_insert' . '__pretransaction_foobar', \Drupal::cache()->get('test_cache_pretransaction_foobar'));
    \Drupal::state()->set('delay_cache_tags_invalidation_entity_test_insert' . '__pretransaction_entity_test_list', \Drupal::cache()->get('test_cache_pretransaction_entity_test_list'));
    // Write during the transaction.
    \Drupal::cache()->set('delay_cache_tags_invalidation_entity_test_insert' . '__during_transaction_foobar', 'something', Cache::PERMANENT, ['foobar']);
    \Drupal::cache()->set('delay_cache_tags_invalidation_entity_test_insert' . '__during_transaction_entity_test_list', 'something', Cache::PERMANENT, ['entity_test_list']);
    // Trigger a nested entity save and hence a nested transaction.
    User::create(['name' => 'john doe', 'status' => 1])->save();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('user_insert')]
  public function userInsert(UserInterface $entity) {
    if ($entity->getAccountName() === 'john doe') {
      // Read the in-transaction cache writes.
      // @see  delay_cache_tags_invalidation_entity_test_insert()
      \Drupal::state()->set('delay_cache_tags_invalidation_user_insert' . '__during_transaction_foobar', \Drupal::cache()->get('delay_cache_tags_invalidation_entity_test_insert__during_transaction_foobar'));
      \Drupal::state()->set('delay_cache_tags_invalidation_user_insert' . '__during_transaction_entity_test_list', \Drupal::cache()->get('delay_cache_tags_invalidation_entity_test_insert__during_transaction_entity_test_list'));
    }
  }

}
