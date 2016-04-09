<?php

namespace Drupal\contact;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for assembly and dispatch of contact mail messages.
 */
interface MailHandlerInterface {

  /**
   * Sends mail messages as appropriate for a given Message form submission.
   *
   * Can potentially send up to three messages as follows:
   * - To the configured recipient;
   * - Auto-reply to the sender; and
   * - Carbon copy to the sender.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   Submitted message entity.
   * @param \Drupal\Core\Session\AccountInterface $sender
   *   User that submitted the message entity form.
   *
   * @throws \Drupal\contact\MailHandlerException
   *   When unable to determine message recipient.
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender);

}
