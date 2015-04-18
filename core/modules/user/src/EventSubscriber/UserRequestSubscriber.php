<?php

/**
 * @file
 * Contains \Drupal\user\EventSubscriber\UserRequestSubscriber.
 */

namespace Drupal\user\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Updates the current user's last access time.
 */
class UserRequestSubscriber implements EventSubscriberInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new UserRequestSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $account, EntityManagerInterface $entity_manager) {
    $this->account = $account;
    $this->entityManager = $entity_manager;
  }

  /**
   * Updates the current user's last access time.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event to process.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    if ($this->account->isAuthenticated() && REQUEST_TIME - $this->account->getLastAccessedTime() > Settings::get('session_write_interval', 180)) {
      // Do that no more than once per 180 seconds.
      /** @var \Drupal\user\UserStorageInterface $storage */
      $storage = $this->entityManager->getStorage('user');
      $storage->updateLastAccessTimestamp($this->account, REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Should go before other subscribers start to write their caches. Notably
    // before \Drupal\Core\EventSubscriber\KernelDestructionSubscriber to
    // prevent instantiation of destructed services.
    $events[KernelEvents::TERMINATE][] = ['onKernelTerminate', 300];
    return $events;
  }

}
