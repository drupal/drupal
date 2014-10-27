<?php

/**
 * @file
 * Contains \Drupal\contact\MessageInterface.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a contact message entity.
 */
interface MessageInterface extends ContentEntityInterface {

  /**
   * Returns the form this contact message belongs to.
   *
   * @return \Drupal\contact\ContactFormInterface
   *   The contact form entity.
   */
  public function getContactForm();

  /**
   * Returns the name of the sender.
   *
   * @return string
   *   The name of the message sender.
   */
  public function getSenderName();

  /**
   * Sets the name of the message sender.
   *
   * @param string $sender_name
   *   The name of the message sender.
   */
  public function setSenderName($sender_name);

  /**
   * Returns the email address of the sender.
   *
   * @return string
   *   The email address of the message sender.
   */
  public function getSenderMail();

  /**
   * Sets the email address of the sender.
   *
   * @param string $sender_mail
   *   The email address of the message sender.
   */
  public function setSenderMail($sender_mail);

  /**
   * Returns the message subject.
   *
   * @return string
   *   The message subject.
   */
  public function getSubject();

  /**
   * Sets the subject for the email.
   *
   * @param string $subject
   *   The message subject.
   */
  public function setSubject($subject);

  /**
   * Returns the message body.
   *
   * @return string
   *   The message body.
   */
  public function getMessage();

  /**
   * Sets the email message to send.
   *
   * @param string $message
   *   The message body.
   */
  public function setMessage($message);

  /**
   * Returns TRUE if a copy should be sent to the sender.
   *
   * @return bool
   *   TRUE if a copy should be sent, FALSE if not.
   */
  public function copySender();

  /**
   * Sets if the sender should receive a copy of this email or not.
   *
   * @param bool $inform
   *   TRUE if a copy should be sent, FALSE if not.
   */
  public function setCopySender($inform);

  /**
   * Returns TRUE if this is the personal contact form.
   *
   * @return bool
   *   TRUE if the message bundle is personal.
   */
  public function isPersonal();

  /**
   * Returns the user this message is being sent to.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the recipent, NULL if this is not a personal message.
   */
  public function getPersonalRecipient();

}
