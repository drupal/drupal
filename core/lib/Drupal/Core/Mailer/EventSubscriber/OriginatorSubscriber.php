<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Message subscriber which sets the from and sender headers.
 */
class OriginatorSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * Sets the default from header and a sender header if necessary.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The message event.
   */
  public function onMessage(MessageEvent $event): void {
    $message = $event->getMessage();
    if ($message instanceof Email) {
      $this->setDefaultFrom($message);
      $this->setDefaultSender($message);
      $this->removeRedundantSender($message);
    }
  }

  /**
   * Sets the default from address.
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   */
  protected function setDefaultFrom(Email $message): void {
    $from = $message->getFrom();
    if (count($from) === 0) {
      $langcode = $message->getHeaders()->get('Content-Language')?->getBodyAsString();
      $siteAddress = $this->getSiteAddress($langcode);
      $message->from($siteAddress);
    }
  }

  /**
   * Sets the default sender address.
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   */
  protected function setDefaultSender(Email $message): void {
    if (!$message->getSender()) {
      $langcode = $message->getHeaders()->get('Content-Language')?->getBodyAsString();
      $siteAddress = $this->getSiteAddress($langcode);
      $message->sender($siteAddress);
    }
  }

  /**
   * Removes the Sender address if it is redundant.
   *
   * Rules according to RFC 5322 section 3.6.2 (Originator Fields):
   * * If the from field contains more than one mailbox specification in the
   *   mailbox-list, then the sender field, containing the field name
   *   "Sender" and a single mailbox specification, MUST appear in the
   *   message.
   * * The "Sender:" field specifies the mailbox of the agent responsible
   *   for the actual transmission of the message.
   * * If the originator of the message can be indicated by a single mailbox
   *   and the author and transmitter are identical, the "Sender:" field
   *   SHOULD NOT be used.
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   *
   * @see https://www.rfc-editor.org/rfc/rfc5322.html#section-3.6.2
   */
  protected function removeRedundantSender(Email $message): void {
    $from = $message->getFrom();
    $sender = $message->getSender();
    $senderRedundant = count($from) === 1 &&
      $sender !== NULL &&
      $from[0]->getAddress() === $sender->getAddress();
    if ($senderRedundant) {
      $message->getHeaders()->remove('Sender');
    }
  }

  /**
   * Returns the site email address.
   *
   * @param string|null $langcode
   *   The language code from the email.
   */
  protected function getSiteAddress(?string $langcode): Address {
    return $this->executeInEnvironment($langcode, function () {
      $config = $this->configFactory->get('system.site');
      return new Address($config->get('mail'), $config->get('name'));
    });
  }

  /**
   * Invokes the given callback after switching the config language.
   *
   * @param string|null $langcode
   *   The language code.
   * @param callable(): T $callback
   *   Callback to execute inside substituted environment.
   *
   * @return T
   *   Returns the result returned by the callback.
   *
   * @template T
   *
   * @todo Replace with execution environment once that is available.
   * @see: https://www.drupal.org/i/3536307
   */
  protected function executeInEnvironment(?string $langcode, callable $callback): mixed {
    $originalLanguage = $this->languageManager->getConfigOverrideLanguage();
    if ($langcode && ($language = $this->languageManager->getLanguage($langcode))) {
      $this->languageManager->setConfigOverrideLanguage($language);
    }
    try {
      return $callback();
    }
    finally {
      $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Should be the last one to allow header changes by other listeners.
      MessageEvent::class => ['onMessage', -255],
    ];
  }

}
