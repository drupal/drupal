<?php

namespace Drupal\block_test\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $account, EntityManagerInterface $entity_manager) {
    $this->account = $account;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $current_user = $this->userStorage->load($this->account->id());

    $context1 = new Context(new ContextDefinition('entity:user', 'User A'), $current_user);

    $context2 = new Context(new ContextDefinition('entity:user', 'User B'), $current_user);

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
