<?php

declare(strict_types=1);

namespace Drupal\update;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * A service to handle assembly and dispatch of Update Status mail messages.
 *
 * @internal
 */
class MailHandler {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly TimeInterface $time,
    /**
     * @var \Closure(): \Drupal\Core\Mail\MailManagerInterface
     */
    #[AutowireServiceClosure('plugin.manager.mail')]
    protected readonly \Closure $mailManager,
  ) {
  }

  /**
   * Sends update notification mails to a list of recipients.
   *
   * @param string[] $recipients
   *   A list of recipient mail addresses.
   * @param array{'core'?: int, 'contrib'?: int} $items
   *   The update status entries for core and contrib projects. Allowed values
   *   are defined as constants on UpdateManagerInterface.
   *
   * @return bool
   *   TRUE if any email notifications were sent, otherwise FALSE.
   *
   * @see \Drupal\update\UpdateManagerInterface
   */
  public function sendUpdateNotifications(array $recipients, array $items): bool {
    $results = [];
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $storage = $this->entityTypeManager->getStorage('user');
    $accounts_by_email = [];
    foreach ($storage->loadByProperties(['mail' => $recipients]) as $account) {
      assert($account instanceof AccountInterface);
      $accounts_by_email[$account->getEmail()] = $account;
    }
    foreach ($recipients as $recipient) {
      // If the recipient is a registered user with a language preference, use
      // the recipient's preferred language. Otherwise, use the system default
      // language.
      $recipient_account = $accounts_by_email[$recipient] ?? NULL;
      $langcode = $recipient_account ? $recipient_account->getPreferredLangcode() : $default_langcode;
      $message = ($this->mailManager)()->mail('update', 'status_notify', $recipient, $langcode, $items);
      $results[] = $message['result'] ?? NULL;
    }
    return count(array_filter($results)) > 0;
  }

}
