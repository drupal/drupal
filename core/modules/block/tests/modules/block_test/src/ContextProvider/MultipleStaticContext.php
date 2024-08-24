<?php

declare(strict_types=1);

namespace Drupal\block_test\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Session\AccountInterface;

/**
 * Sets multiple contexts for a static value.
 */
class MultipleStaticContext implements ContextProviderInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new MultipleStaticContext.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    $this->account = $account;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $current_user = $this->userStorage->load($this->account->id());

    $context1 = EntityContext::fromEntity($current_user, 'User A');
    $context2 = EntityContext::fromEntity($current_user, 'User B');

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['user']);

    $context1->addCacheableDependency($cacheability);
    $context2->addCacheableDependency($cacheability);

    return [
      'userA' => $context1,
      'userB' => $context2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
