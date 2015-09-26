<?php

/**
 * @file
 * Contains \Drupal\contact\MailHandler.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a class for handling assembly and dispatch of contact mail messages.
 */
class MailHandler implements MailHandlerInterface {

  use StringTranslationTrait;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new \Drupal\contact\MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerInterface $logger, TranslationInterface $string_translation, EntityManagerInterface $entity_manager) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender) {
    // Clone the sender, as we make changes to mail and name properties.
    $sender_cloned = clone $this->userStorage->load($sender->id());
    $params = array();
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $recipient_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $contact_form = $message->getContactForm();

    if ($sender_cloned->isAnonymous()) {
      // At this point, $sender contains an anonymous user, so we need to take
      // over the submitted form values.
      $sender_cloned->name = $message->getSenderName();
      $sender_cloned->mail = $message->getSenderMail();

      // For the email message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender_cloned->name = $this->t('@name (not verified)', array('@name' => $message->getSenderName()));
    }

    // Build email parameters.
    $params['contact_message'] = $message;
    $params['sender'] = $sender_cloned;

    if (!$message->isPersonal()) {
      // Send to the form recipient(s), using the site's default language.
      $params['contact_form'] = $contact_form;

      $to = implode(', ', $contact_form->getRecipients());
    }
    elseif ($recipient = $message->getPersonalRecipient()) {
      // Send to the user in the user's preferred language.
      $to = $recipient->getEmail();
      $recipient_langcode = $recipient->getPreferredLangcode();
      $params['recipient'] = $recipient;
    }
    else {
      throw new MailHandlerException('Unable to determine message recipient');
    }

    // Send email to the recipient(s).
    $key_prefix = $message->isPersonal() ? 'user' : 'page';
    $this->mailManager->mail('contact', $key_prefix . '_mail', $to, $recipient_langcode, $params, $sender_cloned->getEmail());

    // If requested, send a copy to the user, using the current language.
    if ($message->copySender()) {
      $this->mailManager->mail('contact', $key_prefix . '_copy', $sender_cloned->getEmail(), $current_langcode, $params, $sender_cloned->getEmail());
    }

    // If configured, send an auto-reply, using the current language.
    if (!$message->isPersonal() && $contact_form->getReply()) {
      // User contact forms do not support an auto-reply message, so this
      // message always originates from the site.
      $this->mailManager->mail('contact', 'page_autoreply', $sender_cloned->getEmail(), $current_langcode, $params);
    }

    if (!$message->isPersonal()) {
      $this->logger->notice('%sender-name (@sender-from) sent an email regarding %contact_form.', array(
        '%sender-name' => $sender_cloned->getUsername(),
        '@sender-from' => $sender_cloned->getEmail(),
        '%contact_form' => $contact_form->label(),
      ));
    }
    else {
      $this->logger->notice('%sender-name (@sender-from) sent %recipient-name an email.', array(
        '%sender-name' => $sender_cloned->getUsername(),
        '@sender-from' => $sender_cloned->getEmail(),
        '%recipient-name' => $message->getPersonalRecipient()->getUsername(),
      ));
    }
  }

}
