<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Implements a service to dispatch user notification messages.
 *
 * @internal
 */
class NotificationHandler {

  /**
   * @phpstan-param \Closure(): \Psr\Log\LoggerInterface $logger
   * @phpstan-param \Closure(): \Drupal\Core\Mail\MailManagerInterface $mailManager
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LanguageManagerInterface $languageManager,
    #[AutowireServiceClosure('logger.channel.user')]
    protected readonly \Closure $logger,
    #[AutowireServiceClosure('plugin.manager.mail')]
    protected readonly \Closure $mailManager,
  ) {
  }

  /**
   * Notifies the user when their account has been created by an administrator.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendRegisterAdminCreated(UserInterface $account): bool {
    return (bool) $this->sendToUser('register_admin_created', $account);
  }

  /**
   * Notifies the user when they have successfully registered an account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendRegisterNoApprovalRequired(UserInterface $account): bool {
    return (bool) $this->sendToUser('register_no_approval_required', $account);
  }

  /**
   * Notifies the user that their registration request is pending approval.
   *
   * Additionally notify an administrator about the newly registered account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendRegisterPendingApproval(UserInterface $account): bool {
    $result = $this->sendToUser('register_pending_approval', $account);
    $this->sendRegisterPendingApprovalAdmin($account);
    return (bool) $result;
  }

  /**
   * Sends a notification email to the admin about an account pending approval.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is pending approval.
   */
  protected function sendRegisterPendingApprovalAdmin(UserInterface $account): void {
    if ($this->configFactory->get('user.settings')->get('notify.register_pending_approval')) {
      $params['account'] = $account;
      $adminAddress = $this->getAdminAddress();
      if ($adminAddress !== NULL) {
        ($this->mailManager)()->mail('user', 'register_pending_approval_admin', $adminAddress, $this->languageManager->getDefaultLanguage()->getId(), $params);
      }
    }
  }

  /**
   * Sends a password reset email to the user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendPasswordReset(UserInterface $account): bool {
    return (bool) $this->sendToUser('password_reset', $account);
  }

  /**
   * Notifies the user that their account has been activated.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendStatusActivated(UserInterface $account): bool {
    return (bool) $this->sendToUser('status_activated', $account);
  }

  /**
   * Notifies the user that their account has been blocked.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendStatusBlocked(UserInterface $account): bool {
    return (bool) $this->sendToUser('status_blocked', $account);
  }

  /**
   * Confirms with the user that they have requested to cancel their account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendCancelConfirm(UserInterface $account): bool {
    return (bool) $this->sendToUser('cancel_confirm', $account);
  }

  /**
   * Notifies the user that their account has been canceled.
   *
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool
   *   TRUE if the email has been sent successfully, FALSE if an error occurred
   *   or if the sending has been canceled.
   */
  public function sendStatusCanceled(UserInterface $account): bool {
    return (bool) $this->sendToUser('status_canceled', $account);
  }

  /**
   * Returns the account admin email address.
   *
   * @return non-empty-string|null
   *   Email address of the account admin.
   */
  protected function getAdminAddress(): ?string {
    // Get the custom site notification email if it has been set.
    $siteConfig = $this->configFactory->get('system.site');
    $adminAddress = $siteConfig->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($adminAddress)) {
      $adminAddress = $siteConfig->get('mail');
    }

    return $adminAddress ?: NULL;
  }

  /**
   * Returns the email address used in the reply-to field.
   *
   * @return non-empty-string|null
   *   Email address used in the reply-to field.
   */
  protected function getReplyToAddress(): ?string {
    return $this->getAdminAddress();
  }

  /**
   * Sends a notification email following a change to a user account.
   *
   * @param string $op
   *   The operation being performed on the account. Possible values:
   *   - 'register_admin_created': Welcome message for user created by the
   *     admin.
   *   - 'register_no_approval_required': Welcome message when user
   *     self-registers.
   *   - 'register_pending_approval': Welcome message, user pending admin
   *     approval.
   *   - 'password_reset': Password recovery request.
   *   - 'status_activated': Account activated.
   *   - 'status_blocked': Account blocked.
   *   - 'cancel_confirm': Account cancellation request.
   *   - 'status_canceled': Account canceled.
   * @param \Drupal\user\UserInterface $account
   *   The entity that represents the user who is being notified.
   *
   * @return bool|null
   *   TRUE if the email has been sent successfully, FALSE if an error occurred,
   *   NULL if the sending has been canceled by an implementation of
   *   hook_mail_alter().
   *
   * @see user_mail_tokens()
   */
  protected function sendToUser(string $op, UserInterface $account): ?bool {
    if ($this->configFactory->get('user.settings')->get('notify.' . $op)) {
      $params['account'] = $account;
      if ($account->getEmail()) {
        $mail = ($this->mailManager)()->mail('user', $op, $account->getEmail(), $account->getPreferredLangcode(), $params, $this->getReplyToAddress());
      }
      else {
        ($this->logger)()->info('The User module could not send an email for the operation "%op" because the user account %account does not have an email address.', [
          '%op' => $op,
          '%account' => $account->getDisplayName(),
        ]);
      }
    }
    return empty($mail) ? NULL : $mail['result'];
  }

}
