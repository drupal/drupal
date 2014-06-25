<?php

/**
 * @file
 * Contains \Drupal\block\Event\CurrentUserContext.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current user as a context.
 */
class CurrentUserContext extends BlockConditionContextSubscriberBase {

  use StringTranslationTrait;

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
   * Constructs a new CurrentUserContext.
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
  protected function determineBlockContext() {
    $current_user = $this->userStorage->load($this->account->id());

    $context = new Context(new ContextDefinition('entity:user', $this->t('Current user')));
    $context->setContextValue($current_user);
    $this->addContext('current_user', $context);
  }

}
