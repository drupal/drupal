<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Finalize user logout.
 */
final readonly class LogoutFinalizer {

  public function __construct(
    protected AccountProxyInterface $accountProxy,
    protected SessionManagerInterface $sessionManager,
    protected ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'logger.channel.user')]
    protected LoggerInterface $logger,
  ) {}

  /**
   * Logs the current user out.
   */
  public function finalizeLogout(): void {
    $this->logger->info('Session closed for %name.', ['%name' => $this->accountProxy->getAccountName()]);

    $this->moduleHandler->invokeAll('user_logout', [$this->accountProxy]);

    // Destroy the current session, and reset $user to the anonymous user.
    // Note: In Symfony the session is intended to be destroyed with
    // Session::invalidate(). Regrettably this method is currently broken and
    // may lead to the creation of spurious session records in the database.
    // @see https://github.com/symfony/symfony/issues/12375
    $this->sessionManager->destroy();
    $this->accountProxy->setAccount(new AnonymousUserSession());
  }

}
