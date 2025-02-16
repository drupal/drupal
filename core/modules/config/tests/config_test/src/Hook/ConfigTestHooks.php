<?php

declare(strict_types=1);

namespace Drupal\config_test\Hook;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_test.
 */
class ConfigTestHooks {

  /**
   * Implements hook_cache_flush().
   */
  #[Hook('cache_flush')]
  public function cacheFlush(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_cache_flush'] = 'config_test_cache_flush';
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    // The 'translatable' entity key is not supposed to change over time. In
    // this case we can safely do it because we set it once and we do not change
    // it for all the duration of the test session.
    $entity_types['config_test']->set('translatable', \Drupal::service('state')->get('config_test.translatable'));
    // Create a clone of config_test that does not have a status.
    $entity_types['config_test_no_status'] = clone $entity_types['config_test'];
    $config_test_no_status =& $entity_types['config_test_no_status'];
    $config_test_no_status->setLinkTemplate('edit-form', '/admin/structure/config_test/manage/{config_test_no_status}');
    $config_test_no_status->setLinkTemplate('delete-form', '/admin/structure/config_test/manage/{config_test_no_status}/delete');
    $keys = $config_test_no_status->getKeys();
    unset($keys['status']);
    $config_test_no_status->set('id', 'config_test_no_status');
    $config_test_no_status->set('entity_keys', $keys);
    $config_test_no_status->set('config_prefix', 'no_status');
    $config_test_no_status->set('mergedConfigExport', ['id' => 'id', 'label' => 'label', 'uuid' => 'uuid', 'langcode' => 'langcode']);
    if (\Drupal::service('state')->get('config_test.lookup_keys', FALSE)) {
      $entity_types['config_test']->set('lookup_keys', ['uuid', 'style']);
    }
    if (\Drupal::service('state')->get('config_test.class_override', FALSE)) {
      $entity_types['config_test']->setClass(\Drupal::service('state')->get('config_test.class_override'));
    }
  }

  /**
   * Implements hook_entity_query_tag__ENTITY_TYPE__TAG_alter().
   *
   * Entity type is 'config_query_test' and tag is
   * 'config_entity_query_alter_hook_test'.
   *
   * @see Drupal\KernelTests\Core\Entity\ConfigEntityQueryTest::testAlterHook
   */
  #[Hook('entity_query_tag__config_query_test__config_entity_query_alter_hook_test_alter')]
  public function entityQueryTagConfigQueryTestConfigEntityQueryAlterHookTestAlter(QueryInterface $query) : void {
    $query->condition('id', '7', '<>');
  }

}
