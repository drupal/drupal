<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Finalize user login.
 */
final readonly class LoginFinalizer {

  public function __construct(
    protected AccountProxyInterface $accountProxy,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'logger.channel.user')]
    protected LoggerInterface $logger,
    protected TimeInterface $time,
  ) {}

  /**
   * Finalize the login process and logs in a user.
   *
   * This method logs in the user, records a watchdog message about the new
   * session, saves the login timestamp, calls hook_user_login(), and generates
   * a new session.
   *
   * The current user is replaced with the passed in account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account to log in.
   */
  public function finalizeLogin(UserInterface $user): void {
    $this->accountProxy->setAccount($user);
    $this->logger->info('Session opened for %name.', ['%name' => $user->getAccountName()]);
    // Update the user table timestamp noting user has logged in.
    // This is also used to invalidate one-time login links.
    $user->setLastLoginTime($this->time->getRequestTime());
    $this->entityTypeManager->getStorage('user')
      ->updateLastLoginTimestamp($user);

    // Regenerate the session ID to prevent against session fixation attacks.
    // This is called before hook_user_login() in case one of those functions
    // fails or incorrectly does a redirect which would leave the old session
    // in place.
    $session = $this->requestStack->getSession();
    $session->migrate();
    $session->set('uid', $user->id());
    $session->set('check_logged_in', TRUE);
    $this->moduleHandler->invokeAll('user_login', [$user]);
  }

}
